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
     * @var bool isCompiled indicates if routes have been compiled for optimized matching
     */
    protected $isCompiled = false;

    /**
     * @var array staticRoutes hash map for O(1) static route lookup
     */
    protected $staticRoutes = [];

    /**
     * @var string|null dynamicRegex combined regex for dynamic routes
     */
    protected $dynamicRegex = null;

    /**
     * @var array dynamicRouteMap maps regex group index to route info
     */
    protected $dynamicRouteMap = [];

    /**
     * @var array wildcardRules ordered list of wildcard route rule names
     */
    protected $wildcardRules = [];

    /**
     * route registers a new route rule
     */
    public function route($name, $route)
    {
        $this->invalidate();
        return $this->routeMap[$name] = Rule::fromPattern($name, $route);
    }

    /**
     * match given URL string using compiled route matching for performance
     * @param string $url Request URL to match for
     * @return bool
     */
    public function match($url)
    {
        // Reset any previous matches
        $this->matchedRouteRule = null;
        $this->parameters = [];

        // Compile routes on first match if not already compiled
        if (!$this->isCompiled) {
            $this->compile();
        }

        $normalizedUrl = Helper::normalizeUrl($url);

        // Tier 1: Try static route lookup (O(1))
        if ($this->matchStatic($normalizedUrl)) {
            return true;
        }

        // Tier 2: Try combined regex for dynamic routes (O(1))
        if ($this->matchDynamic($normalizedUrl)) {
            return true;
        }

        // Tier 3: Try wildcard routes sequentially (O(w))
        if ($this->matchWildcard($url)) {
            return true;
        }

        return false;
    }

    /**
     * matchStatic attempts O(1) static route lookup
     * @param string $normalizedUrl
     * @return bool
     */
    protected function matchStatic($normalizedUrl)
    {
        $urlLower = mb_strtolower($normalizedUrl);

        if (!isset($this->staticRoutes[$urlLower])) {
            return false;
        }

        $ruleName = $this->staticRoutes[$urlLower];
        if (!isset($this->routeMap[$ruleName])) {
            return false;
        }

        $routeRule = $this->routeMap[$ruleName];
        $parameters = [];

        // Check condition callback
        $callback = $routeRule->condition();
        if ($callback !== null) {
            $callbackResult = call_user_func($callback, $parameters, $normalizedUrl);
            if ($callbackResult === false) {
                return false;
            }
        }

        $this->matchedRouteRule = $routeRule;

        // Run afterMatch callback
        $matchCallback = $routeRule->afterMatch();
        if ($matchCallback !== null) {
            $parameters = call_user_func($matchCallback, $parameters, $normalizedUrl);
        }

        $this->parameters = $parameters;
        return true;
    }

    /**
     * matchDynamic attempts combined regex match for dynamic routes
     * @param string $normalizedUrl
     * @return bool
     */
    protected function matchDynamic($normalizedUrl)
    {
        if ($this->dynamicRegex === null) {
            return false;
        }

        if (!preg_match($this->dynamicRegex, $normalizedUrl, $matches)) {
            return false;
        }

        // Extract matched route and parameters
        $result = RouteCompiler::extractMatchedRoute($matches, $this->dynamicRouteMap);
        if ($result === null) {
            return false;
        }

        [$routeIndex, $parameters] = $result;
        $ruleName = $this->dynamicRouteMap[$routeIndex]['ruleName'];

        if (!isset($this->routeMap[$ruleName])) {
            return false;
        }

        $routeRule = $this->routeMap[$ruleName];

        // Check condition callback
        $callback = $routeRule->condition();
        if ($callback !== null) {
            $callbackResult = call_user_func($callback, $parameters, $normalizedUrl);
            if ($callbackResult === false) {
                return false;
            }
        }

        $this->matchedRouteRule = $routeRule;

        // Run afterMatch callback
        $matchCallback = $routeRule->afterMatch();
        if ($matchCallback !== null) {
            $parameters = call_user_func($matchCallback, $parameters, $normalizedUrl);
        }

        $this->parameters = $parameters;
        return true;
    }

    /**
     * matchWildcard attempts sequential wildcard route matching
     * @param string $url
     * @return bool
     */
    protected function matchWildcard($url)
    {
        $segments = Helper::segmentizeUrl($url, false);
        $normalizedUrl = Helper::normalizeUrl($url);

        foreach ($this->wildcardRules as $ruleName) {
            if (!isset($this->routeMap[$ruleName])) {
                continue;
            }

            $routeRule = $this->routeMap[$ruleName];
            $parameters = [];

            if ($routeRule->resolveUrlSegments($segments, $parameters)) {
                // Check condition callback
                $callback = $routeRule->condition();
                if ($callback !== null) {
                    $callbackResult = call_user_func($callback, $parameters, $normalizedUrl);
                    if ($callbackResult === false) {
                        continue;
                    }
                }

                $this->matchedRouteRule = $routeRule;

                // Run afterMatch callback
                $matchCallback = $routeRule->afterMatch();
                if ($matchCallback !== null) {
                    $parameters = call_user_func($matchCallback, $parameters, $url);
                }

                $this->parameters = $parameters;
                return true;
            }
        }

        return false;
    }

    /**
     * compile builds optimized lookup structures for all registered routes
     * @return $this
     */
    public function compile()
    {
        $compiled = RouteCompiler::compile($this->routeMap);

        $this->staticRoutes = $compiled['staticRoutes'];
        $this->dynamicRegex = $compiled['dynamicRegex'];
        $this->dynamicRouteMap = $compiled['dynamicRouteMap'];
        $this->wildcardRules = $compiled['wildcardRules'];
        $this->isCompiled = true;

        return $this;
    }

    /**
     * invalidate clears compiled state when routes change
     * @return void
     */
    protected function invalidate()
    {
        $this->isCompiled = false;
        $this->staticRoutes = [];
        $this->dynamicRegex = null;
        $this->dynamicRouteMap = [];
        $this->wildcardRules = [];
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
     * hasRoute checks if a named route exists (like Laravel's hasNamedRoute)
     * @param string $name
     * @return bool
     */
    public function hasRoute($name)
    {
        return isset($this->routeMap[$name]);
    }

    /**
     * getRoute returns a route rule by name (like Laravel's getByName)
     * @param string $name
     * @return Rule|null
     */
    public function getRoute($name)
    {
        return $this->routeMap[$name] ?? null;
    }

    /**
     * isCompiled returns whether routes have been compiled
     * @return bool
     */
    public function isCompiled()
    {
        return $this->isCompiled;
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
        $this->invalidate();
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
     * @deprecated Use setCompiledRoutes() for better performance
     */
    public function fromArray($data)
    {
        // Support both old format (array of rules) and new format (with compiled state)
        $rules = isset($data['rules']) ? $data['rules'] : $data;

        foreach ($rules as $route) {
            $this->routeMap[$route['ruleName']] = new Rule($route);
        }

        // Load compiled state if present and version matches
        if (isset($data['compiled']) && $data['compiled']['version'] === RouteCompiler::COMPILED_VERSION) {
            $this->setCompiledRoutes($data['compiled']);
        }
    }

    /**
     * toArray converts the rules to an array including compiled state.
     * @return array
     */
    public function toArray()
    {
        $this->sortRules();

        $rules = [];
        foreach ($this->routeMap as $rule) {
            $rules[] = $rule->toArray();
        }

        return [
            'rules' => $rules,
            'compiled' => $this->getCompiledRoutes(),
        ];
    }

    /**
     * setCompiledRoutes sets the compiled route data directly (like Laravel)
     * This bypasses compilation and uses pre-compiled data for maximum performance.
     * @param array $compiled The compiled route data
     * @return $this
     */
    public function setCompiledRoutes(array $compiled)
    {
        if (!isset($compiled['version']) || $compiled['version'] !== RouteCompiler::COMPILED_VERSION) {
            // Version mismatch, need to recompile
            return $this;
        }

        $this->staticRoutes = $compiled['staticRoutes'] ?? [];
        $this->dynamicRegex = $compiled['dynamicRegex'] ?? null;
        $this->dynamicRouteMap = $compiled['dynamicRouteMap'] ?? [];
        $this->wildcardRules = $compiled['wildcardRules'] ?? [];
        $this->isCompiled = true;

        return $this;
    }

    /**
     * getCompiledRoutes returns the compiled route data
     * @return array
     */
    public function getCompiledRoutes()
    {
        if (!$this->isCompiled) {
            $this->compile();
        }

        return [
            'version' => RouteCompiler::COMPILED_VERSION,
            'staticRoutes' => $this->staticRoutes,
            'dynamicRegex' => $this->dynamicRegex,
            'dynamicRouteMap' => $this->dynamicRouteMap,
            'wildcardRules' => $this->wildcardRules,
        ];
    }

    /**
     * saveCompiledRoutes saves compiled routes to a PHP file (like Laravel's route:cache)
     * The file can be loaded later with loadCompiledRoutes() for instant route matching.
     * @param string $path File path to save to
     * @return bool
     */
    public function saveCompiledRoutes($path)
    {
        $this->sortRules();

        $rules = [];
        foreach ($this->routeMap as $rule) {
            $rules[] = $rule->toArray();
        }

        $data = [
            'rules' => $rules,
            'compiled' => $this->getCompiledRoutes(),
        ];

        $content = '<?php return ' . var_export($data, true) . ';' . PHP_EOL;

        return file_put_contents($path, $content) !== false;
    }

    /**
     * loadCompiledRoutes loads compiled routes from a PHP file
     * This is the fastest way to initialize routes - just include the cached file.
     * @param string $path File path to load from
     * @return static|null Returns router instance or null if file doesn't exist
     */
    public static function loadCompiledRoutes($path)
    {
        if (!file_exists($path)) {
            return null;
        }

        $data = include $path;

        if (!is_array($data)) {
            return null;
        }

        $router = new static;
        $router->fromArray($data);

        return $router;
    }
}
