<?php namespace October\Rain\Router;

/**
 * RouteCompiler transforms route rules into optimized lookup structures,
 * similar to Laravel's cached routes and Symfony's compiled URL matcher.
 *
 * - Fully static routes are collected in a hash map for O(1) lookup.
 * - Dynamic routes are combined into a single regular expression using the
 *   PCRE (*MARK) verb, so one preg_match call identifies the matched route.
 * - Wildcard routes use capture logic that cannot be expressed in the
 *   combined regex, they are matched sequentially by the router.
 *
 * Custom segment expressions (:param|^regex$) are intentionally not inlined
 * in the combined regex. They are validated after the structural match using
 * the same logic as Rule::resolveUrlSegments, preserving their original
 * semantics (case sensitivity, substring search, error tolerance).
 *
 * The route map must be sorted (see Router::sortRules) before compiling,
 * positions recorded here refer to offsets in the sorted route map.
 *
 * @package october\router
 * @author Alexey Bobkov, Samuel Georges
 */
class RouteCompiler
{
    /**
     * @var int COMPILED_VERSION invalidates cached compiled data when the format changes
     */
    const COMPILED_VERSION = 1;

    /**
     * compile transforms route rules into optimized lookup structures
     * @param array $routeMap [ruleName => Rule] in matching (sorted) order
     * @return array
     */
    public static function compile(array $routeMap): array
    {
        $staticRoutes = [];
        $patterns = [];
        $dynamicRouteMap = [];
        $fallbackRules = [];
        $position = 0;
        $index = 0;

        foreach ($routeMap as $name => $rule) {
            // Wildcard rules keep the original sequential matching. The
            // leading static segments are stored so the router can skip the
            // rule cheaply when they cannot match.
            if ($rule->wildSegmentCount > 0) {
                $prefix = [];
                foreach ($rule->segments as $segment) {
                    if (strpos($segment, ':') === 0) {
                        break;
                    }
                    $prefix[] = mb_strtolower($segment);
                }

                $fallbackRules[] = [
                    'name' => $name,
                    'position' => $position,
                    'prefix' => $prefix,
                ];
            }
            // Fully static rule, hash map lookup. The first rule wins,
            // matching sequential behavior.
            elseif ($rule->dynamicSegmentCount === 0) {
                $url = mb_strtolower('/' . implode('/', $rule->segments));
                if (!isset($staticRoutes[$url])) {
                    $staticRoutes[$url] = ['name' => $name, 'position' => $position];
                }
            }
            // Dynamic rule, becomes a branch in the combined regex
            else {
                $compiled = static::compileRulePattern($rule);
                $patterns[] = $compiled['pattern'] . '(*MARK:' . $index . ')';
                $dynamicRouteMap[$index] = [
                    'name' => $name,
                    'position' => $position,
                    'params' => $compiled['params'],
                ];
                $index++;
            }

            $position++;
        }

        return [
            'version' => static::COMPILED_VERSION,
            'staticRoutes' => $staticRoutes,
            'dynamicRegex' => $patterns
                ? '#^(?|' . implode('|', $patterns) . ')$#i'
                : null,
            'dynamicRouteMap' => $dynamicRouteMap,
            'fallbackRules' => $fallbackRules,
        ];
    }

    /**
     * compileRulePattern converts a rule to a regex branch with parameter metadata.
     * Segments with custom expressions match as generic segments ([^/]+) and are
     * validated after the match, so the combined regex never embeds user patterns.
     * @param Rule $rule
     * @return array ['pattern' => string, 'params' => array]
     */
    protected static function compileRulePattern(Rule $rule): array
    {
        $segments = $rule->segments;
        $segmentCount = count($segments);

        // Count trailing optional segments. An optional segment followed by a
        // required segment is treated as required, same as Rule::resolveUrlSegments.
        $trailingOptionals = 0;
        for ($i = $segmentCount - 1; $i >= 0; $i--) {
            if (strpos($segments[$i], ':') === 0 && Helper::segmentIsOptional($segments[$i])) {
                $trailingOptionals++;
            }
            else {
                break;
            }
        }

        $pattern = '';
        $params = [];

        foreach ($segments as $index => $segment) {
            // Static segment
            if (strpos($segment, ':') !== 0) {
                $pattern .= '/' . preg_quote($segment, '#');
                continue;
            }

            // Dynamic segment
            $isTrailingOptional = $index >= $segmentCount - $trailingOptionals;
            $regexp = Helper::getSegmentRegExp($segment);

            $params[] = [
                'name' => Helper::getParameterName($segment),
                'regex' => $regexp !== false ? $regexp : null,
                'default' => $isTrailingOptional
                    ? Helper::getSegmentDefaultValue($segment)
                    : false,
            ];

            if (!$isTrailingOptional) {
                $pattern .= '/([^/]+)';
            }
            elseif ($pattern === '') {
                // Rule starts with an optional segment, it must still match
                // the root URL (/)
                $pattern .= '/([^/]+)?';
            }
            else {
                $pattern .= '(?:/([^/]+))?';
            }
        }

        return ['pattern' => $pattern, 'params' => $params];
    }
}
