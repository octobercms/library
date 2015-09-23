<?php namespace October\Rain\Router;

/**
 * URL Router
 *
 * Used in October CMS for managing page routes.
 *
 * @package october\router
 * @author Alexey Bobkov, Samuel Georges
 */
class Router
{

    /**
     * @var string Value to use when a required parameter is not specified
     */
    public static $defaultValue = 'default';

    /**
     * @var array A list of specified routes
     */
    protected $routeMap = [];

    /**
     * @var \October\Rain\Router\Rule A refered to the matched router rule
     */
    protected $matchedRouteRule;

    /**
     * @var array A list of parameters names and values extracted from the URL pattern and URL string
     */
    protected $parameters = [];

    /**
     * Registers a new route rule
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
        $parameters = [];

        foreach ($this->routeMap as $name => $routeRule) {
            if ($routeRule->resolveUrl($url, $parameters)) {

                $this->matchedRouteRule = $routeRule;

                // If this route has a condition, run it
                $callback = $routeRule->condition();
                if ($callback !== null) {
                    $callbackResult = call_user_func($callback, $parameters, $url);

                    // Callback responded to abort
                    if ($callbackResult === false) {
                        $parameters = [];
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
     * @param array $parameters Parameter name => value items to fill in for given route.
     * @return string Full matched URL as string with given values put in place of named parameters
     */
    public function url($name, $parameters = [])
    {
        if (!isset($this->routeMap[$name]))
            return null;

        $routeRule = $this->routeMap[$name];
        $pattern = $routeRule->pattern();
        return $this->urlFromPattern($pattern, $parameters);
    }

    /**
     * Builds a URL together by matching route pattern and supplied parameters
     *
     * @param string $pattern Route pattern string, eg: /path/to/something/:parameter
     * @param array $parameters Parameter name => value items to fill in for given route.
     * @return string Full matched URL as string with given values put in place of named parameters
     */
    public function urlFromPattern($pattern, $parameters = [])
    {
        $patternSegments = Helper::segmentizeUrl($pattern);
        $patternSegmentNum = count($patternSegments);

        /*
         * Normalize the parameters, colons (:) in key names are removed.
         */
        foreach ($parameters as $param => $value) {
            if (strpos($param, ':') !== 0) continue;
            $normalizedParam = substr($param, 1);
            $parameters[$normalizedParam] = $value;
            unset($parameters[$param]);
        }

        // Build a URL
        $url = [];
        foreach ($patternSegments as $index => $patternSegment) {

            /*
             * Static segment
             */
            if (strpos($patternSegment, ':') !== 0) {
                $url[] = $patternSegment;
                continue;
            }
            /*
             * Dynamic segment
             */
            else {
                /*
                 * Get the parameter name
                 */
                $paramName = Helper::getParameterName($patternSegment);

                /*
                 * Determine whether it is optional
                 */

                $optional = Helper::segmentIsOptional($patternSegment);

                /*
                 * Check if parameter has been supplied
                 */
                $parameterExists = array_key_exists($paramName, $parameters);

                /*
                 * Use supplied parameter value
                 */
                if ($parameterExists) {
                    $url[] = $parameters[$paramName];
                }
                /*
                 * Look for default value or set as false
                 */
                elseif ($optional) {
                    $url[] = Helper::getSegmentDefaultValue($patternSegment);
                }
                /*
                 * Non optional field, use the default value
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
            if ($segment) {
                $lastPopulatedIndex = $index;
            }
            else {
                $url[$index] = static::$defaultValue;
            }
        }

        $url = array_slice($url, 0, $lastPopulatedIndex + 1);

        return Helper::rebuildUrl($url);
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
     * Clears all existing routes
     * @return Self
     */
    public function reset()
    {
        $this->routeMap = [];
        return $this;
    }

    /**
     * Sorts all the routing rules by static segments, then dynamic
     * @return void
     */
    public function sortRules()
    {
        uasort($this->routeMap, function($a, $b) {
            $lengthA = $a->staticSegmentCount;
            $lengthB = $b->staticSegmentCount;

            if ($lengthA > $lengthB) {
                return -1;
            }
            else if ($lengthA < $lengthB) {
                return 1;
            }
            else {
                $lengthA = $a->dynamicSegmentCount;
                $lengthB = $b->dynamicSegmentCount;

                if ($lengthA > $lengthB) {
                    return 1;
                }
                else if ($lengthA < $lengthB) {
                    return -1;
                }
                else {
                    return 0;
                }
            }
        });
    }

}