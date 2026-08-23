<?php namespace October\Rain\Router;

use Exception;

/**
 * Router used in October CMS for managing page routes.
 *
 * Matching uses compiled lookup structures (see RouteCompiler): a hash map
 * for static routes, a combined regex for dynamic routes and sequential
 * matching for wildcard routes. Compilation happens lazily on the first
 * match and sorts the route map by specificity (see sortRules), the compiled
 * result can be cached and restored via toArray/fromArray.
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
     * @var array routeMap is a list of specified routes. Rules restored from
     * cached data are stored as raw config arrays and hydrated to Rule
     * objects on demand.
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
     * @var array dynamicRegexes combined regexes for dynamic routes, keyed
     * by the first static URL segment
     */
    protected $dynamicRegexes = [];

    /**
     * @var array dynamicRouteMap maps a combined regex mark to route info
     */
    protected $dynamicRouteMap = [];

    /**
     * @var array fallbackRules are rules matched sequentially (wildcard rules),
     * each with the position they hold in the sorted route map
     */
    protected $fallbackRules = [];

    /**
     * route registers a new route rule
     */
    public function route($name, $route)
    {
        $this->invalidateCompiled();

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
        $this->parameters = [];

        if (!$this->isCompiled) {
            $this->compileRules();
        }

        $segments = Helper::segmentizeUrl($url, false);
        $plainUrl = '/' . implode('/', $segments);
        $lowerUrl = mb_strtolower($plainUrl);
        $lowerSegments = $segments ? explode('/', substr($lowerUrl, 1)) : [];

        // Static route lookup
        $static = $this->staticRoutes[$lowerUrl] ?? null;
        if ($static !== null) {
            $routeRule = $this->ruleObject($static['name']);
            if ($routeRule && $this->acceptRuleMatch($routeRule, [], $url)) {
                return true;
            }

            // The condition callback rejected the match, continue with the
            // rules that follow, like sequential matching does
            return $this->matchFromPosition($segments, $url, $static['position'] + 1);
        }

        // Dynamic route lookup, a combined regex identifies the matched
        // route using the (*MARK) verb. Routes bucketed by the URL's first
        // segment and routes starting with a dynamic segment ('' bucket)
        // are both checked, the earliest sorted match wins.
        $candidate = null;
        if ($this->dynamicRegexes) {
            $buckets = $lowerSegments ? [$lowerSegments[0], ''] : [''];

            foreach ($buckets as $bucket) {
                if (!isset($this->dynamicRegexes[$bucket])) {
                    continue;
                }

                $result = @preg_match($this->dynamicRegexes[$bucket], $plainUrl, $matches);

                // Regex engine failure (e.g. backtrack limit), fall back to
                // sequential matching
                if ($result === false) {
                    return $this->matchFromPosition($segments, $url, 0);
                }

                if ($result === 1) {
                    $found = $this->extractDynamicCandidate($matches);
                    if ($found !== null && ($candidate === null || $found['position'] < $candidate['position'])) {
                        $candidate = $found;
                    }
                }
            }
        }

        // Wildcard rules are matched sequentially. Only those that outrank
        // the dynamic candidate in the sorted route map are considered first.
        foreach ($this->fallbackRules as $fallback) {
            if ($candidate !== null && $fallback['position'] > $candidate['position']) {
                break;
            }

            // The leading static segments must match for the rule to apply
            foreach ($fallback['prefix'] as $index => $staticSegment) {
                if (($lowerSegments[$index] ?? null) !== $staticSegment) {
                    continue 2;
                }
            }

            $routeRule = $this->ruleObject($fallback['name']);
            if (!$routeRule) {
                continue;
            }

            $parameters = [];
            if (
                $routeRule->resolveUrlSegments($segments, $parameters) &&
                $this->acceptRuleMatch($routeRule, $parameters, $url)
            ) {
                return true;
            }
        }

        if ($candidate !== null) {
            if ($candidate['valid']) {
                $routeRule = $this->ruleObject($candidate['name']);
                if ($routeRule && $this->acceptRuleMatch($routeRule, $candidate['parameters'], $url)) {
                    return true;
                }
            }

            // A custom segment expression or condition callback rejected the
            // candidate, continue with the rules that follow
            return $this->matchFromPosition($segments, $url, $candidate['position'] + 1);
        }

        return false;
    }

    /**
     * matchFromPosition matches sequentially starting from a position in the
     * sorted route map, used when compiled matching rejects a candidate
     * @param array $segments
     * @param string $url
     * @param int $position
     * @return bool
     */
    protected function matchFromPosition($segments, $url, $position)
    {
        $rules = $position > 0
            ? array_slice($this->routeMap, $position, null, true)
            : $this->routeMap;

        foreach ($rules as $name => $routeRule) {
            if (is_array($routeRule)) {
                $routeRule = $this->ruleObject($name);
            }

            $parameters = [];
            if (
                $routeRule->resolveUrlSegments($segments, $parameters) &&
                $this->acceptRuleMatch($routeRule, $parameters, $url)
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * acceptRuleMatch runs the rule condition, stores the matched rule and
     * processed parameters. Returns false when the condition rejects the match.
     * @param Rule $routeRule
     * @param array $parameters
     * @param string $url
     * @return bool
     */
    protected function acceptRuleMatch($routeRule, $parameters, $url)
    {
        // If this route has a condition, run it
        $callback = $routeRule->condition();
        if ($callback !== null) {
            $callbackResult = call_user_func($callback, $parameters, Helper::normalizeUrl($url));

            // Callback responded to abort
            if ($callbackResult === false) {
                return false;
            }
        }

        $this->matchedRouteRule = $routeRule;

        // If this route has a match callback, run it
        $matchCallback = $routeRule->afterMatch();
        if ($matchCallback !== null) {
            $parameters = call_user_func($matchCallback, $parameters, $url);
        }

        $this->parameters = $parameters;

        return true;
    }

    /**
     * extractDynamicCandidate builds the matched route candidate from a
     * combined regex match, validating custom segment expressions with the
     * same logic as Rule::resolveUrlSegments
     * @param array $matches
     * @return array|null ['name', 'position', 'parameters', 'valid']
     */
    protected function extractDynamicCandidate($matches)
    {
        if (!isset($matches['MARK'])) {
            return null;
        }

        $routeInfo = $this->dynamicRouteMap[(int) $matches['MARK']] ?? null;
        if ($routeInfo === null) {
            return null;
        }

        $parameters = [];
        $valid = true;

        foreach ($routeInfo['params'] as $index => $param) {
            $value = $matches[$index + 1] ?? '';

            // Unmatched optional segment, use the default value
            if ($value === '') {
                $parameters[$param['name']] = $param['default'];
                continue;
            }

            // Validate the value with the custom regular expression
            if ($param['regex'] !== null) {
                try {
                    if (!preg_match($param['regex'], $value)) {
                        $valid = false;
                        break;
                    }
                }
                catch (Exception $ex) {
                }
            }

            $parameters[$param['name']] = $value;
        }

        return [
            'name' => $routeInfo['name'],
            'position' => $routeInfo['position'],
            'parameters' => $parameters,
            'valid' => $valid,
        ];
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
        $routeRule = $this->ruleObject($name);
        if (!$routeRule) {
            return null;
        }

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
        $this->hydrateRules();

        return $this->routeMap;
    }

    /**
     * hasRoute checks if a named route exists
     * @param string $name
     * @return bool
     */
    public function hasRoute($name)
    {
        return isset($this->routeMap[$name]);
    }

    /**
     * getRoute returns a route rule by name
     * @param string $name
     * @return Rule|null
     */
    public function getRoute($name)
    {
        return $this->ruleObject($name);
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
        $this->invalidateCompiled();
        return $this;
    }

    /**
     * sortRules sorts all the routing rules by static segments (long to short),
     * then dynamic segments (short to long), then wild segments (at end).
     * @return void
     */
    public function sortRules()
    {
        $this->hydrateRules();

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
     * compileRules sorts the route map and builds the compiled lookup
     * structures used for optimized matching
     * @return $this
     */
    public function compileRules()
    {
        $this->sortRules();

        $this->setCompiledRoutes(RouteCompiler::compile($this->routeMap));

        return $this;
    }

    /**
     * ruleObject returns a rule by name, hydrating it from raw config
     * if needed
     * @param string $name
     * @return Rule|null
     */
    protected function ruleObject($name)
    {
        $rule = $this->routeMap[$name] ?? null;

        if (is_array($rule)) {
            $rule = $this->routeMap[$name] = new Rule($rule);
        }

        return $rule;
    }

    /**
     * hydrateRules converts any raw rule configs to Rule objects
     * @return void
     */
    protected function hydrateRules()
    {
        foreach ($this->routeMap as $name => $rule) {
            if (is_array($rule)) {
                $this->routeMap[$name] = new Rule($rule);
            }
        }
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
     * invalidateCompiled clears compiled state when routes change
     * @return void
     */
    protected function invalidateCompiled()
    {
        $this->isCompiled = false;
        $this->staticRoutes = [];
        $this->dynamicRegexes = [];
        $this->dynamicRouteMap = [];
        $this->fallbackRules = [];
    }

    /**
     * setCompiledRoutes restores compiled route data, positions in the data
     * must correspond to the current route map order. Data with a version
     * mismatch is ignored and routes are recompiled on the next match.
     * @param array $compiled
     * @return $this
     */
    public function setCompiledRoutes(array $compiled)
    {
        if (($compiled['version'] ?? null) !== RouteCompiler::COMPILED_VERSION) {
            return $this;
        }

        // Reject torn or corrupted payloads, e.g. from a concurrent cache
        // write, the routes recompile on the next match instead
        if (
            !is_array($compiled['staticRoutes'] ?? null) ||
            !is_array($compiled['dynamicRegexes'] ?? null) ||
            !is_array($compiled['dynamicRouteMap'] ?? null) ||
            !is_array($compiled['fallbackRules'] ?? null)
        ) {
            return $this;
        }

        $this->staticRoutes = $compiled['staticRoutes'];
        $this->dynamicRegexes = $compiled['dynamicRegexes'];
        $this->dynamicRouteMap = $compiled['dynamicRouteMap'];
        $this->fallbackRules = $compiled['fallbackRules'];
        $this->isCompiled = true;

        return $this;
    }

    /**
     * getCompiledRoutes returns the compiled route data, compiling first
     * if necessary
     * @return array
     */
    public function getCompiledRoutes()
    {
        if (!$this->isCompiled) {
            $this->compileRules();
        }

        return [
            'version' => RouteCompiler::COMPILED_VERSION,
            'staticRoutes' => $this->staticRoutes,
            'dynamicRegexes' => $this->dynamicRegexes,
            'dynamicRouteMap' => $this->dynamicRouteMap,
            'fallbackRules' => $this->fallbackRules,
        ];
    }

    /**
     * fromArray loads routes from an array, including compiled state when
     * present. Supports the legacy format (a plain list of rules).
     */
    public function fromArray($data)
    {
        $rules = isset($data['rules']) && is_array($data['rules'])
            ? $data['rules']
            : $data;

        foreach ($rules as $route) {
            // Store the raw config, rules are hydrated to objects on demand
            $this->routeMap[$route['ruleName']] = $route;
        }

        if (isset($data['compiled']) && is_array($data['compiled'])) {
            $this->setCompiledRoutes($data['compiled']);
        }
    }

    /**
     * toArray converts the rules to an array, including the compiled state
     * for cache storage.
     * @return array
     */
    public function toArray()
    {
        // Compiling sorts the route map, it must happen before the rules
        // are exported so their order matches the compiled positions
        $compiled = $this->getCompiledRoutes();

        $rules = [];
        foreach ($this->routeMap as $rule) {
            $rules[] = is_array($rule) ? $rule : $rule->toArray();
        }

        return [
            'rules' => $rules,
            'compiled' => $compiled,
        ];
    }

    /**
     * saveCompiledRoutes saves the routes with their compiled state to a PHP
     * file, similar to Laravel's route:cache. Load it later with
     * loadCompiledRoutes for instant route matching.
     * @param string $path File path to save to
     * @return bool
     */
    public function saveCompiledRoutes($path)
    {
        $content = '<?php return ' . var_export($this->toArray(), true) . ';' . PHP_EOL;

        return file_put_contents($path, $content) !== false;
    }

    /**
     * loadCompiledRoutes loads compiled routes from a PHP file
     * @param string $path File path to load from
     * @return static|null Returns a router instance or null if the file doesn't exist
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
