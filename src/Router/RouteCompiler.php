<?php namespace October\Rain\Router;

/**
 * RouteCompiler transforms route rules into optimized lookup structures
 * for faster route matching using static hash maps and combined regexes.
 *
 * Uses the same approach as Laravel:
 * - Static routes: Hash map for O(1) lookup
 * - Dynamic routes: Combined regex with PCRE (*MARK) verb
 * - Trailing slash normalization
 *
 * @package october\router
 * @author Alexey Bobkov, Samuel Georges
 */
class RouteCompiler
{
    /**
     * @var int COMPILED_VERSION for cache format versioning
     */
    const COMPILED_VERSION = 3;

    /**
     * compile transforms route rules into optimized lookup structures
     * @param array $routeMap [ruleName => Rule]
     * @return array Compiled route data
     */
    public static function compile(array $routeMap): array
    {
        // Sort routes first for consistent ordering
        uasort($routeMap, [static::class, 'compareRules']);

        // Extract wildcard routes (cannot be combined into regex)
        [$wildcardRoutes, $remainingRoutes] = static::extractWildcardRoutes($routeMap);

        // Build static route hash map (with trailing slash normalization)
        [$staticRoutes, $dynamicRoutes] = static::buildStaticRoutes($remainingRoutes);

        // Build combined regex for dynamic routes
        $dynamicCompiled = static::buildCombinedRegex($dynamicRoutes);

        return [
            'version' => static::COMPILED_VERSION,
            'staticRoutes' => $staticRoutes,
            'dynamicRegex' => $dynamicCompiled['regex'],
            'dynamicRouteMap' => $dynamicCompiled['routeMap'],
            'wildcardRules' => array_keys($wildcardRoutes),
        ];
    }

    /**
     * compareRules sorts rules by specificity (static segments, wildcards, dynamic)
     * @param Rule $a
     * @param Rule $b
     * @return int
     */
    protected static function compareRules(Rule $a, Rule $b): int
    {
        // More static segments = more specific = check first
        if ($a->staticSegmentCount !== $b->staticSegmentCount) {
            return $b->staticSegmentCount - $a->staticSegmentCount;
        }

        // Fewer wildcards = more specific = check first
        if ($a->wildSegmentCount !== $b->wildSegmentCount) {
            return $a->wildSegmentCount - $b->wildSegmentCount;
        }

        // Fewer dynamic segments = more specific = check first
        return $a->dynamicSegmentCount - $b->dynamicSegmentCount;
    }

    /**
     * extractWildcardRoutes separates routes with wildcard segments
     * @param array $routeMap
     * @return array [wildcardRoutes, remainingRoutes]
     */
    protected static function extractWildcardRoutes(array $routeMap): array
    {
        $wildcardRoutes = [];
        $remainingRoutes = [];

        foreach ($routeMap as $name => $rule) {
            if ($rule->wildSegmentCount > 0) {
                $wildcardRoutes[$name] = $rule;
            }
            else {
                $remainingRoutes[$name] = $rule;
            }
        }

        return [$wildcardRoutes, $remainingRoutes];
    }

    /**
     * buildStaticRoutes extracts fully static routes into hash map
     * Also handles trailing slash normalization (like Symfony)
     * @param array $routeMap
     * @return array [staticRoutes, dynamicRoutes]
     */
    protected static function buildStaticRoutes(array $routeMap): array
    {
        $staticRoutes = [];
        $dynamicRoutes = [];

        foreach ($routeMap as $name => $rule) {
            if ($rule->dynamicSegmentCount === 0) {
                // Fully static route - use lowercase for case-insensitive matching
                $url = mb_strtolower(Helper::rebuildUrl($rule->segments));
                $staticRoutes[$url] = $name;

                // Also register with/without trailing slash for normalization
                if ($url !== '/') {
                    $withSlash = rtrim($url, '/') . '/';
                    $withoutSlash = rtrim($url, '/');
                    if (!isset($staticRoutes[$withSlash])) {
                        $staticRoutes[$withSlash] = $name;
                    }
                    if (!isset($staticRoutes[$withoutSlash])) {
                        $staticRoutes[$withoutSlash] = $name;
                    }
                }
            }
            else {
                $dynamicRoutes[$name] = $rule;
            }
        }

        return [$staticRoutes, $dynamicRoutes];
    }

    /**
     * buildCombinedRegex creates a single regex from all dynamic routes
     * Uses PCRE (*MARK:n) verb like Symfony for efficient route identification
     * @param array $dynamicRoutes
     * @param int $startIndex Starting index for route numbering (for chunking)
     * @return array ['regex' => string|null, 'routeMap' => array]
     */
    protected static function buildCombinedRegex(array $dynamicRoutes, int $startIndex = 0): array
    {
        if (empty($dynamicRoutes)) {
            return ['regex' => null, 'routeMap' => []];
        }

        $patterns = [];
        $routeMap = [];
        $index = $startIndex;

        foreach ($dynamicRoutes as $name => $rule) {
            $routePattern = static::ruleToRegex($rule, $index);

            if ($routePattern !== null) {
                $patterns[] = $routePattern['pattern'];
                $routeMap[$index] = [
                    'ruleName' => $name,
                    'params' => $routePattern['params'],
                    'defaults' => $routePattern['defaults'],
                ];
                $index++;
            }
        }

        if (empty($patterns)) {
            return ['regex' => null, 'routeMap' => []];
        }

        // Combine all patterns with alternation
        // Using 'i' for case-insensitive, 'u' for UTF-8 support
        $combinedRegex = '#^(?|' . implode('|', $patterns) . ')$#iu';

        return ['regex' => $combinedRegex, 'routeMap' => $routeMap];
    }

    /**
     * ruleToRegex converts a Rule to a regex pattern with (*MARK:n) verb
     * @param Rule $rule
     * @param int $routeIndex
     * @return array|null ['pattern' => string, 'params' => array, 'defaults' => array]
     */
    protected static function ruleToRegex(Rule $rule, int $routeIndex): ?array
    {
        $segments = $rule->segments;
        $regexParts = [];
        $params = [];
        $defaults = [];
        $hasTrailingOptional = false;

        // Process segments from end to detect trailing optionals
        $segmentCount = count($segments);
        $trailingOptionalCount = 0;

        for ($i = $segmentCount - 1; $i >= 0; $i--) {
            $segment = $segments[$i];
            if (strpos($segment, ':') === 0 && Helper::segmentIsOptional($segment)) {
                $trailingOptionalCount++;
            }
            else {
                break;
            }
        }

        // Build regex for each segment
        foreach ($segments as $idx => $segment) {
            // Static segment
            if (strpos($segment, ':') !== 0) {
                $regexParts[] = preg_quote($segment, '#');
            }
            // Dynamic segment
            else {
                $paramName = Helper::getParameterName($segment);
                $params[] = $paramName;

                // Get custom regex or use default
                $customRegex = Helper::getSegmentRegExp($segment);
                if ($customRegex) {
                    // Extract inner pattern (remove delimiters and anchors)
                    $innerPattern = trim($customRegex, '/');
                    // Strip start/end anchors as they don't apply within combined regex
                    $innerPattern = preg_replace('/^\^/', '', $innerPattern);
                    $innerPattern = preg_replace('/\$$/', '', $innerPattern);
                }
                else {
                    $innerPattern = '[^/]+';
                }

                // Capture group for parameter (unnamed, will use numeric index)
                $groupPattern = "({$innerPattern})";

                // Handle optional segments
                $isOptional = Helper::segmentIsOptional($segment);
                $isTrailingOptional = $isOptional && ($idx >= $segmentCount - $trailingOptionalCount);

                if ($isOptional) {
                    $default = Helper::getSegmentDefaultValue($segment);
                    if ($default !== false) {
                        $defaults[$paramName] = $default;
                    }
                    else {
                        $defaults[$paramName] = null;
                    }
                }

                if ($isTrailingOptional) {
                    // Trailing optional - make the whole segment optional including leading slash
                    $regexParts[] = "(?:/{$groupPattern})?";
                    $hasTrailingOptional = true;
                }
                else {
                    $regexParts[] = $groupPattern;
                }
            }
        }

        // Join with slashes, handling trailing optionals specially
        if ($hasTrailingOptional) {
            // Find where trailing optionals start
            $mainParts = [];
            $optionalParts = [];
            $inOptional = false;

            foreach ($regexParts as $part) {
                if (strpos($part, '(?:/') === 0) {
                    $inOptional = true;
                }

                if ($inOptional) {
                    $optionalParts[] = $part;
                }
                else {
                    $mainParts[] = $part;
                }
            }

            // Handle case where all segments are optional (e.g., /:page?)
            if (empty($mainParts)) {
                // Convert first optional from (?:/X)? to /X? format to match root /
                if (!empty($optionalParts)) {
                    $first = array_shift($optionalParts);
                    // Transform (?:/([^/]+))? to /([^/]+)?
                    $first = preg_replace('#^\(\?:/(.+)\)\?$#', '/$1?', $first);
                    $pattern = $first . implode('', $optionalParts);
                }
                else {
                    $pattern = '/';
                }
            }
            else {
                $pattern = '/' . implode('/', $mainParts) . implode('', $optionalParts);
            }
        }
        else {
            $pattern = '/' . implode('/', $regexParts);
        }

        // Add (*MARK:n) verb to identify which route matched (like Symfony)
        $pattern = $pattern . '(*MARK:' . $routeIndex . ')';

        return [
            'pattern' => $pattern,
            'params' => $params,
            'defaults' => $defaults,
        ];
    }

    /**
     * extractMatchedRoute extracts route index and parameters from regex match
     * @param array $matches preg_match result
     * @param array $routeMap compiled route map
     * @return array|null [routeIndex, parameters]
     */
    public static function extractMatchedRoute(array $matches, array $routeMap): ?array
    {
        // Get route index from MARK (like Symfony)
        if (!isset($matches['MARK'])) {
            return null;
        }

        $matchedIndex = (int) $matches['MARK'];

        if (!isset($routeMap[$matchedIndex])) {
            return null;
        }

        $routeInfo = $routeMap[$matchedIndex];
        $parameters = [];

        // Extract parameter values from numeric capture groups
        // Groups start at index 1 (0 is full match)
        foreach ($routeInfo['params'] as $i => $paramName) {
            $groupIndex = $i + 1;
            if (isset($matches[$groupIndex]) && $matches[$groupIndex] !== '') {
                $parameters[$paramName] = $matches[$groupIndex];
            }
            elseif (isset($routeInfo['defaults'][$paramName])) {
                $parameters[$paramName] = $routeInfo['defaults'][$paramName];
            }
            else {
                $parameters[$paramName] = false;
            }
        }

        return [$matchedIndex, $parameters];
    }
}
