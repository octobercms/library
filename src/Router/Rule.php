<?php namespace October\Rain\Router;

use InvalidArgumentException;
use Exception;

/**
 * Rule object for routes
 *
 * @package october\router
 * @author Alexey Bobkov, Samuel Georges
 */
class Rule
{
    /**
     * @var array config values for this instance
     */
    protected $config = [];

    /**
     * @var string ruleName is a named reference for this rule.
     */
    protected $ruleName;

    /**
     * @var string rulePattern used to match this rule.
     */
    protected $rulePattern;

    /**
     * @var function conditionCallback used when matching this rule.
     */
    protected $conditionCallback;

    /**
     * @var function afterMatchCallback called when this rule is matched.
     */
    protected $afterMatchCallback;

    /**
     * @var string staticUrl with static segments only, dynamic segments are stripped
     */
    public $staticUrl;

    /**
     * @var array segments for the pattern
     */
    public $segments;

    /**
     * @var int segmentCount is the number of segments in the pattern
     */
    public $segmentCount = 0;

    /**
     * @var int staticSegmentCount the number of static segments found in the pattern
     */
    public $staticSegmentCount = 0;

    /**
     * @var int dynamicSegmentCount the number of dynamic segments found in the pattern
     */
    public $dynamicSegmentCount = 0;

    /**
     * @var int wildSegmentCount the number of wildcard segments found in the pattern
     */
    public $wildSegmentCount = 0;

    /**
     * __construct the new router rule instance.
     *
     * @param string $name
     * @param string $pattern
     */
    public function __construct($config = [])
    {
        $this->config = $config;

        foreach ($config as $key => $val) {
            $this->{$key} = $val;
        }
    }

    /**
     * fromPattern returns a named rule from a pattern
     */
    public static function fromPattern($name, $pattern): static
    {
        $segments = Helper::segmentizeUrl($pattern);

        // Create the static URL for this pattern for reverse lookup
        //
        $staticSegments = [];
        $staticSegmentCount = $dynamicSegmentCount = $wildSegmentCount = 0;

        foreach ($segments as $segment) {
            if (strpos($segment, ':') !== 0) {
                $staticSegments[] = $segment;
                $staticSegmentCount++;
            }
            else {
                $dynamicSegmentCount++;

                if (Helper::segmentIsWildcard($segment)) {
                    $wildSegmentCount++;
                }
            }
        }

        $staticUrl = Helper::rebuildUrl($staticSegments);

        // Build and return rule
        //
        $rule = new static([
            'ruleName' => $name,
            'rulePattern' => $pattern,
            'segments' => $segments,
            'segmentCount' => count($segments),
            'staticUrl' => $staticUrl,
            'staticSegments' => $staticSegments,
            'staticSegmentCount' => $staticSegmentCount,
            'dynamicSegmentCount' => $dynamicSegmentCount,
            'wildSegmentCount' => $wildSegmentCount,
        ]);

        return $rule;
    }

    /**
     * resolveUrl checks whether a given URL matches a given pattern, with a reference to a PHP array
     * variable to return the parameter list fetched from URL. Returns true if the URL matches the
     * pattern. Otherwise returns false.
     * @param string $url
     * @param array $parameters
     * @return bool
     */
    public function resolveUrl($url, &$parameters)
    {
        return $this->resolveUrlSegments(Helper::segmentizeUrl($url), $parameters);
    }

    /**
     * resolveUrlSegments is an internal method used for multiple checks.
     * @param array $urlSegments
     * @param array $parameters
     * @return bool
     */
    public function resolveUrlSegments($urlSegments, &$parameters)
    {
        $parameters = [];

        // Only one wildcard can be used, if found, pull out the excess segments
        $wildSegments = [];
        if ($this->wildSegmentCount === 1) {
            $wildSegments = $this->captureWildcardSegments($urlSegments);
        }

        // If the number of URL segments is more than the number of pattern segments
        if (count($urlSegments) > $this->segmentCount) {
            return false;
        }

        // Compare pattern and URL segments
        foreach ($this->segments as $index => $patternSegment) {
            // Static segment
            if (strpos($patternSegment, ':') !== 0) {
                if (
                    !array_key_exists($index, $urlSegments) ||
                    mb_strtolower($patternSegment) !== mb_strtolower($urlSegments[$index])
                ) {
                    return false;
                }
            }
            // Dynamic segment
            else {
                // Initialize the parameter
                $paramName = Helper::getParameterName($patternSegment);
                $parameters[$paramName] = false;

                // Determine whether it is optional
                $optional = Helper::segmentIsOptional($patternSegment);

                // Check if the optional segment has no required segments following it
                if ($optional && $index < ($this->segmentCount - 1)) {
                    for ($i = $index+1; $i < $this->segmentCount; $i++) {
                        if (!Helper::segmentIsOptional($this->segments[$i])) {
                            $optional = false;
                            break;
                        }
                    }
                }

                // If the segment is optional and there is no corresponding value in the URL,
                // assign the default value (if provided) and skip to the next segment.
                $urlSegmentExists = array_key_exists($index, $urlSegments);

                if ($optional && !$urlSegmentExists) {
                    $parameters[$paramName] = Helper::getSegmentDefaultValue($patternSegment);
                    continue;
                }

                // If the segment is not optional and there is no corresponding value in the URL
                if (!$optional && !$urlSegmentExists) {
                    return false;
                }

                // Validate the value with the regular expression
                $regexp = Helper::getSegmentRegExp($patternSegment);

                if ($regexp) {
                    try {
                        if (!preg_match($regexp, $urlSegments[$index])) {
                            return false;
                        }
                    }
                    catch (Exception $ex) {
                    }
                }

                // Set the parameter value
                $parameters[$paramName] = $urlSegments[$index];

                // Determine if wildcard and add stored parameters as a suffix
                if (Helper::segmentIsWildcard($patternSegment) && count($wildSegments)) {
                    $parameters[$paramName] .= Helper::rebuildUrl($wildSegments);
                }
            }
        }

        return true;
    }

    /**
     * captureWildcardSegments captures and removes every segment of a URL after a wildcard
     * pattern segment is detected, until both collections of segments
     * are the same size.
     * @param array $urlSegments
     * @return array
     */
    protected function captureWildcardSegments(&$urlSegments)
    {
        $wildSegments = [];
        $patternSegments = $this->segments;
        $segmentDiff = count($urlSegments) - count($patternSegments);
        $wildMode = false;
        $wildCount = 0;

        foreach ($urlSegments as $index => $urlSegment) {
            if ($wildMode) {
                if ($wildCount < $segmentDiff) {
                    $wildSegments[] = $urlSegment;
                    $wildCount++;
                    unset($urlSegments[$index]);
                    continue;
                }

                break;
            }

            $patternSegment = $patternSegments[$index];
            if (Helper::segmentIsWildcard($patternSegment)) {
                $wildMode = true;
            }
        }

        // Reset array index
        $urlSegments = array_values($urlSegments);

        return $wildSegments;
    }

    /**
     * name is a unique route name
     *
     * @param string $name Unique name for the router object
     * @return object Self
     */
    public function name($name = null)
    {
        if ($name === null) {
            return $this->ruleName;
        }

        $this->ruleName = $name;

        return $this;
    }

    /**
     * pattern for the route match
     *
     * @param string $pattern Pattern used to match this rule
     * @return self
     */
    public function pattern($pattern = null)
    {
        if ($pattern === null) {
            return $this->rulePattern;
        }

        $this->rulePattern = $pattern;

        return $this;
    }

    /**
     * condition callback
     *
     * @param callback $callback Callback function to be used when providing custom route match conditions
     * @throws InvalidArgumentException When supplied argument is not a valid callback
     * @return callback
     */
    public function condition($callback = null)
    {
        if ($callback !== null) {
            if (!is_callable($callback)) {
                throw new InvalidArgumentException(sprintf(
                    "Condition provided is not a valid callback. Given (%s)",
                    gettype($callback)
                ));
            }

            $this->conditionCallback = $callback;

            return $this;
        }

        return $this->conditionCallback;
    }

    /**
     * afterMatch callback
     *
     * @param callback $callback Callback function to be used to modify params after a successful match
     * @throws InvalidArgumentException When supplied argument is not a valid callback
     * @return callback
     */
    public function afterMatch($callback = null)
    {
        if ($callback !== null) {
            if (!is_callable($callback)) {
                throw new InvalidArgumentException(sprintf(
                    "The after match callback provided is not valid. Given (%s)",
                    gettype($callback)
                ));
            }

            $this->afterMatchCallback = $callback;

            return $this;
        }

        return $this->afterMatchCallback;
    }

    /**
     * toArray
     */
    public function toArray()
    {
        return $this->config;
    }
}
