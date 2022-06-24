<?php namespace October\Rain\Router;

/**
 * Router used in October CMS for managing page routes.
 *
 * @package october\router
 * @author Alexey Bobkov, Samuel Georges
 */
class Router
{
    /**
     * @var string defaultValue to use when a required parameter is not specified
     */
    public static $defaultValue = 'default';

    /**
     * @var array routeMap is a list of specified routes
     */
    protected $routeMap = [];

    /**
     * @var \October\Rain\Router\Rule matchedRouteRule reference
     */
    protected $matchedRouteRule;

    /**
     * @var array parameters with names and values extracted from the URL pattern and URL string
     */
    protected $parameters = [];

    /**
     * route registers a new route rule
     */
    public function route($name, $route)
    {
        return $this->routeMap[$name] = Rule::fromPattern($name, $route);
    }

    /**
     * match given URL string
     * @param string $url Request URL to match for
     * @return bool
     */
    public function match($url)
    {
        // Reset any previous matches
        $this->matchedRouteRule = null;

        $segments = Helper::segmentizeUrl($url, false);

        $parameters = [];
        foreach ($this->routeMap as $routeRule) {
            if ($routeRule->resolveUrlSegments($segments, $parameters)) {
                $this->matchedRouteRule = $routeRule;

                // If this route has a condition, run it
                $callback = $routeRule->condition();
                if ($callback !== null) {
                    $callbackResult = call_user_func($callback, $parameters, Helper::normalizeUrl($url));

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

        return $this->matchedRouteRule ? true : false;
    }

    /**
     * url builds a URL together by matching route name and supplied parameters
     *
     * @param string $name Name of the route previously defined.
     * @param array $parameters Parameter name => value items to fill in for given route.
     * @return string Full matched URL as string with given values put in place of named parameters
     */
    public function url($name, $parameters = [])
    {
        if (!isset($this->routeMap[$name])) {
            return null;
        }

        $routeRule = $this->routeMap[$name];

        $pattern = $routeRule->pattern();

        return $this->urlFromPattern($pattern, $parameters);
    }

    /**
     * urlFromPattern builds a URL together by matching route pattern and supplied parameters
     *
     * @param string $pattern Route pattern string, eg: /path/to/something/:parameter
     * @param array $parameters Parameter name => value items to fill in for given route.
     * @return string Full matched URL as string with given values put in place of named parameters
     */
    public function urlFromPattern($pattern, $parameters = [])
    {
        $patternSegments = Helper::segmentizeUrl($pattern);

        // Normalize the parameters, colons (:) in key names are removed.
        //
        foreach ($parameters as $param => $value) {
            if (strpos($param, ':') !== 0) {
                continue;
            }
            $normalizedParam = substr($param, 1);
            $parameters[$normalizedParam] = $value;
            unset($parameters[$param]);
        }

        // Build the URL segments, remember the last populated index
        //
        $url = [];
        $lastPopulatedIndex = 0;

        foreach ($patternSegments as $index => $patternSegment) {
            // Static segment
            if (strpos($patternSegment, ':') !== 0) {
                $url[] = $patternSegment;
            }
            // Dynamic segment
            else {
                $paramName = Helper::getParameterName($patternSegment);

                // Determine whether it is optional
                $optional = Helper::segmentIsOptional($patternSegment);

                // Default value
                $defaultValue = Helper::getSegmentDefaultValue($patternSegment);

                // Check if parameter has been supplied and is not a default value
                $parameterExists = isset($parameters[$paramName]) &&
                    strlen($parameters[$paramName]) &&
                    $parameters[$paramName] !== $defaultValue;

                // Use supplied parameter value
                if ($parameterExists) {
                    $url[] = $parameters[$paramName];
                }
                // Look for a specified default value
                elseif ($optional) {
                    $url[] = $defaultValue ?: static::$defaultValue;

                    // Do not set $lastPopulatedIndex
                    continue;
                }
                // Non optional field, use the default value
                else {
                    $url[] = static::$defaultValue;
                }
            }

            $lastPopulatedIndex = $index;
        }

        // Trim the URL to only include populated segments
        $url = array_slice($url, 0, $lastPopulatedIndex + 1);

        return Helper::rebuildUrl($url);
    }

    /**
     * getRouteMap returns the active list of router rule objects
     * @return array An associative array with keys matching the route rule names and
     * values matching the router rule object.
     */
    public function getRouteMap()
    {
        return $this->routeMap;
    }

    /**
     * getParameters returns a list of parameters specified in the requested page URL.
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
     * matchedRoute returns the matched route rule name.
     * @return \October\Rain\Router\Rule The matched rule object.
     */
    public function matchedRoute()
    {
        if (!$this->matchedRouteRule) {
            return false;
        }

        return $this->matchedRouteRule->name();
    }

    /**
     * reset clears all existing routes
     * @return $this
     */
    public function reset()
    {
        $this->routeMap = [];
        return $this;
    }

    /**
     * sortRules sorts all the routing rules by static segments (long to short),
     * then dynamic segments (short to long), then wild segments (at end).
     * @return void
     */
    public function sortRules()
    {
        uasort($this->routeMap, function ($a, $b) {
            // When comparing static, longer tails go to the start
            $lengthA = $a->staticSegmentCount;
            $lengthB = $b->staticSegmentCount;

            if ($lengthA > $lengthB) {
                return -1;
            }

            if ($lengthA < $lengthB) {
                return 1;
            }

            // When static tails are equal, push wilds to the end
            $lengthA = $a->wildSegmentCount;
            $lengthB = $b->wildSegmentCount;

            if ($lengthA > $lengthB) {
                return 1;
            }

            if ($lengthA < $lengthB) {
                return -1;
            }

            // When comparing dynamic, longer tails go to the end
            $lengthA = $a->dynamicSegmentCount;
            $lengthB = $b->dynamicSegmentCount;

            if ($lengthA > $lengthB) {
                return 1;
            }

            if ($lengthA < $lengthB) {
                return -1;
            }

            return 0;
        });
    }

    /**
     * fromArray loads routes from an array.
     */
    public function fromArray($routes)
    {
        foreach ($routes as $route) {
            $this->routeMap[$route['ruleName']] = new Rule($route);
        }
    }

    /**
     * toArray converts the rules to an array.
     * @return array
     */
    public function toArray()
    {
        $this->sortRules();

        $rules = [];

        foreach ($this->routeMap as $rule) {
            $rules[] = $rule->toArray();
        }

        return $rules;
    }
}
