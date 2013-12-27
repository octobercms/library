<?php namespace October\Rain\Router;

class Router
{

    /**
     * @var string Value to use when a required parameter is not specified.
     */
    public static $defaultValue = 'default';

    /**
     * @var array A list of specified routes
     */
    protected $routeMap = array();

    /**
     * @var \October\Rain\Router\Rule A refered to the matched router rule.
     */
    protected $matchedRouteRule;

    /**
     * @var array A list of parameters names and values extracted from the URL pattern and URL string.
     */
    private $parameters = array();

    /**
     * Registers a new route rule.
     */
    public function route($name, $route)
    {
        return $this->routeMap[$name] = new Rule($name, $route);
    }

    /**
     * Match given URL string
     *
     * @param string $url Request URL to match for
     * @return array $parameters A reference to a PHP array variable to return the parameter list fetched from URL.
     */
    public function match($url)
    {
        // Reset any previous matches
        $this->matchedRouteRule = null;

        $url = Helper::normalizeUrl($url);
        $parameters = array();

        foreach ($this->routeMap as $name => $routeRule) {
            if ($this->resolveUrl($routeRule, $url, $parameters)) {

                // If this route has a condition, run it
                $callback = $routeRule->condition();
                if ($callback !== null) {
                    $callbackResult = call_user_func($callback, $parameters, $url);

                    // Callback responded to abort
                    if ($callbackResult === false) {
                        $parameters = array();
                        $this->matchedRouteRule = null;
                        continue;
                    }
                }

                break;
            }
        }
        
        // Success
        if ($this->matchedRouteRule) {

            // If this route has a match callback, run it
            $matchCallback = $routeRule->afterMatch();
            if ($matchCallback !== null) {
                $parameters = call_user_func($matchCallback, $parameters, $url);
            }

        }

        $this->parameters = $parameters;
        return ($this->matchedRouteRule) ? true : false;
    }

    /**
     * Builds a URL together by matching route name and supplied parameters
     *
     * @param string $name Name of the route previously defined.
     * @param array $params Parameter name => value items to fill in for given route.
     * @return string Full matched URL as string with given values put in place of named parameters
     */
    public function url($name, $parameters = [])
    {
        if (!isset($this->routeMap[$name]))
            return null;

        $routeRule = $this->routeMap[$name];
        $pattern = $routeRule->pattern();
        $patternSegments = Helper::segmetizeUrl($pattern);
        $patternSegmentNum = count($patternSegments);

        // Build a URL
        $url = [];
        foreach ($patternSegments as $index => $patternSegment) {
            
            /* 
             * Static segment.
             */
            if (strpos($patternSegment, ':') !== 0) {
                $url[] = $patternSegment;
                continue;
            }
            /*
             * Dynamic segment.
             */
            else {
                /*
                 * Get the parameter name.
                 */
                $paramName = $this->getParameterName($patternSegment);

                /*
                 * Determine whether it is optional.
                 */
                
                $optional = $this->segmentIsOptional($patternSegment);

                /*
                 * Check if parameter has been supplied
                 */
                $parameterExists = array_key_exists($paramName, $parameters);

                /*
                 * Use supplied parameter value.
                 */
                if ($parameterExists) {
                    $url[] = $parameters[$paramName];
                }
                /*
                 * Look for default value or set as false.
                 */
                elseif ($optional) {
                    $url[] = $this->getSegmentDefaultValue($patternSegment);
                }
                /*
                 * Non optional field, use the default value.
                 */
                else {
                    $url[] = static::$defaultValue;
                }

            }
        }

        /*
         * Trim the URL array and set any empty inbetween values to default value
         */
        $lastPopulatedIndex = 0;
        foreach ($url as $index => $segment) {
            if ($segment)
                $lastPopulatedIndex = $index;
            else
                $url[$index] = static::$defaultValue;
        }

        $url = array_slice($url, 0, $lastPopulatedIndex + 1);

        return Helper::rebuildUrl($url);
    }

    /**
     * Checks whether a given URL matches a given pattern.
     * @param \October\Rain\Router\Rule $routeRule Router rule object.
     * @param string $url The URL to check.
     * @param array $parameters A reference to a PHP array variable to return the parameter list fetched from URL.
     * @return boolean Returns true if the URL matches the pattern. Otherwise returns false.
     */
    public function resolveUrl(Rule $routeRule, $url, &$parameters)
    {
        $parameters = array();
        $pattern = $routeRule->pattern();

        $urlSegments = Helper::segmetizeUrl($url);
        $patternSegments = Helper::segmetizeUrl($pattern);
        $patternSegmentNum = count($patternSegments);

        /*
         * If the number of URL segments is more than the number of pattern segments - return false
         */
         
        if (count($urlSegments) > count($patternSegments))
            return false;
         
        /*
         * Compare pattern and URL segments
         */

        foreach ($patternSegments as $index=>$patternSegment) {
            $patternSegmentLower = mb_strtolower($patternSegment);
            
            if (strpos($patternSegment, ':') !== 0) {
                /* 
                 * Static segment.
                 */
                
                if (!array_key_exists($index, $urlSegments) || $patternSegmentLower != mb_strtolower($urlSegments[$index]))
                    return false;
            }
            else {
                
                /*
                 * Dynamic segment. Initialize the parameter.
                 */
                
                $paramName = $this->getParameterName($patternSegment);
                $parameters[$paramName] = false;
                
                /*
                 * Determine whether it is optional.
                 */
                
                $optional = $this->segmentIsOptional($patternSegment);

                /*
                 * Check if the optional segment has no required segments following it.
                 */

                if ($optional && $index < $patternSegmentNum-1) {
                    for ($i = $index+1; $i < $patternSegmentNum; $i++) {
                        if (!$this->segmentIsOptional($patternSegments[$i])) {
                            $optional = false;
                            break;
                        }
                    }
                }
                
                /*
                 * If the segment is optional and there is no corresponding value in the URL, assign the default value (if provided)
                 * and skip to the next segment.
                 */

                $urlSegmentExists = array_key_exists($index, $urlSegments);

                if ($optional && !$urlSegmentExists) {
                    $parameters[$paramName] = $this->getSegmentDefaultValue($patternSegment);
                    continue;
                }

                /*
                 * If the segment is not optional and there is no corresponding value in the URL, return false
                 */
                
                if (!$optional && !$urlSegmentExists)
                    return false;
                
                /*
                 * Validate the value with the regular expression
                 */
                
                $regexp = $this->getSegmentRegExp($patternSegment);

                if ($regexp) {
                    try {
                        if (!preg_match($regexp, $urlSegments[$index]))
                            return false;
                    } catch (\Exception $ex) {}
                }
                
                /*
                 * Set the parameter value
                 */
                
                $parameters[$paramName] = $urlSegments[$index];
            }
        }
        
        $this->matchedRouteRule = $routeRule;
        return true;
    }
    
    /**
     * Returns the active list of router rule objects
     * @return array An associative array with keys matching the route rule names and 
     * values matching the router rule object.
     */
    public function getRouteMap()
    {
        return $this->routeMap;
    }

    /**
     * Returns a list of parameters specified in the requested page URL. 
     * For example, if the URL pattern was /blog/post/:id and the actual URL 
     * was /blog/post/10, the $parameters['id'] element would be 10.
     * @return array An associative array with keys matching the parameter names specified in the URL pattern and 
     * values matching the corresponding segments of the actual requested URL.
     */
    public function getParameters()
    {
        return $this->parameters;
    }

    /**
     * Returns the matched route rule name.
     * @return \October\Rain\Router\Rule The matched rule object.
     */
    public function matchedRoute()
    {
        if (!$this->matchedRouteRule)
            return false;

        return $this->matchedRouteRule->name();
    }    
    
    /**
     * Checks whether an URL pattern segment is optional.
     * @param string $segment The segment definition.
     * @return boolean Returns boolean true if the segment is optional. Returns false otherwise.
     */
    protected function segmentIsOptional(&$segment)
    {
        $name = mb_substr($segment, 1);
        
        $optMarkerPos = mb_strpos($name, '?');
        if ($optMarkerPos === false)
            return false;
        
        $regexMarkerPos = mb_strpos($name, '|');
        if ($regexMarkerPos === false)
            return true;
        
        if ($optMarkerPos !== false && $regexMarkerPos !== false)
            return $optMarkerPos < $regexMarkerPos;

        return false;
    }
    
    /**
     * Extracts the parameter name from a URL pattern segment definition.
     * @param string $segment The segment definition.
     * @return string Returns the segment name.
     */
    protected function getParameterName(&$segment)
    {
        $name = mb_substr($segment, 1);
        
        $optMarkerPos = mb_strpos($name, '?');
        $regexMarkerPos = mb_strpos($name, '|');
        
        if ($optMarkerPos !== false && $regexMarkerPos !== false) {
            if ($optMarkerPos < $regexMarkerPos)
                return mb_substr($name, 0, $optMarkerPos);
            else
                return mb_substr($name, 0, $regexMarkerPos);
        }

        if ($optMarkerPos !== false)
            return mb_substr($name, 0, $optMarkerPos);

        if ($regexMarkerPos !== false)
            return mb_substr($name, 0, $regexMarkerPos);

        return $name;
    }
    
    /**
     * Extracts the regular expression from a URL pattern segment definition.
     * @param string $segment The segment definition.
     * @return string Returns the regular expression string or false if the expression is not defined.
     */
    protected function getSegmentRegExp(&$segment)
    {
        if (($pos = mb_strpos($segment, '|')) !== false) {
            $regexp = mb_substr($segment, $pos+1);
            if (!mb_strlen($regexp))
                return false;
                
            return '/'.$regexp.'/';
        }

        return false;
    }
    
    /**
     * Extracts the default parameter value from a URL pattern segment definition.
     * @param string $segment The segment definition.
     * @return string Returns the default value if it is provided. Returns false otherwise.
     */
    protected function getSegmentDefaultValue(&$segment)
    {
        $optMarkerPos = mb_strpos($segment, '?');
        if ($optMarkerPos === false)
            return false;

        $regexMarkerPos = mb_strpos($segment, '|');
        $value = false;
        
        if ($regexMarkerPos !== false)
            $value = mb_substr($segment, $optMarkerPos+1, $regexMarkerPos-$optMarkerPos-1);
        else
            $value = mb_substr($segment, $optMarkerPos+1);

        return strlen($value) ? $value : false;
    }

    /**
     * Clears all existing routes
     * @return Self
     */
    public function reset()
    {
        $this->routeMap = array();
        return $this;
    }

}