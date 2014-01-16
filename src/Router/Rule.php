<?php namespace October\Rain\Router;

use InvalidArgumentException;

/**
 * Router Rule Object
 *
 * @package october\router
 * @author Alexey Bobkov, Samuel Georges
 */
class Rule
{

    /**
     * @var string A named reference for this rule.
     */
    protected $ruleName;

    /**
     * @var string The pattern used to match this rule.
     */
    protected $rulePattern;

    /**
     * @var function Custom condition used when matching this rule.
     */
    protected $conditionCallback;

    /**
     * @var function Called when this rule is matched.
     */
    protected $afterMatchCallback;

    /**
     * @var string URL with static segments only, dynamic segments are stripped
     */
    public $staticUrl;

    /**
     * @var array Pattern segments
     */
    public $segments;

    /**
     * @var int The number of static segments found in the pattern
     */
    public $staticSegmentCount = 0;

    /**
     * @var int The number of dynamic segments found in the pattern
     */
    public $dynamicSegmentCount = 0;

    /**
     * Creates a new router rule instance.
     *
     * @param string $name
     * @param string $pattern
     */
    public function __construct($name, $pattern)
    {
        $this->ruleName = $name;
        $this->rulePattern = $pattern;
        $this->segments = Helper::segmentizeUrl($pattern);

        /*
         * Create the static URL for this pattern
         */
        $staticSegments = [];
        foreach ($this->segments as $segment) {
            if (strpos($segment, ':') !== 0) {
                $staticSegments[] = $segment;
                $this->staticSegmentCount++;
            }
            else
                $this->dynamicSegmentCount++;
        }

        $this->staticUrl = Helper::rebuildUrl($staticSegments);
    }

    /**
     * Checks whether a given URL matches a given pattern.
     * @param string $url The URL to check.
     * @param array $parameters A reference to a PHP array variable to return the parameter list fetched from URL.
     * @return boolean Returns true if the URL matches the pattern. Otherwise returns false.
     */
    public function resolveUrl($url, &$parameters)
    {
        $parameters = array();

        $patternSegments = $this->segments;
        $patternSegmentNum = count($patternSegments);
        $urlSegments = Helper::segmentizeUrl($url);

        /*
         * If the number of URL segments is more than the number of pattern segments - return false
         */
        if (count($urlSegments) > count($patternSegments))
            return false;

        /*
         * Compare pattern and URL segments
         */
        foreach ($patternSegments as $index => $patternSegment) {
            $patternSegmentLower = mb_strtolower($patternSegment);

            if (strpos($patternSegment, ':') !== 0) {

                /*
                 * Static segment
                 */
                if (!array_key_exists($index, $urlSegments) || $patternSegmentLower != mb_strtolower($urlSegments[$index]))
                    return false;
            }
            else {

                /*
                 * Dynamic segment. Initialize the parameter
                 */
                $paramName = Helper::getParameterName($patternSegment);
                $parameters[$paramName] = false;

                /*
                 * Determine whether it is optional
                 */
                $optional = Helper::segmentIsOptional($patternSegment);

                /*
                 * Check if the optional segment has no required segments following it
                 */
                if ($optional && $index < $patternSegmentNum-1) {
                    for ($i = $index+1; $i < $patternSegmentNum; $i++) {
                        if (!Helper::segmentIsOptional($patternSegments[$i])) {
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
                    $parameters[$paramName] = Helper::getSegmentDefaultValue($patternSegment);
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
                $regexp = Helper::getSegmentRegExp($patternSegment);

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

        return true;
    }

    /**
     * Unique route name
     *
     * @param string $name Unique name for the router object
     * @return object Self
     */
    public function name($name = null)
    {
        if ($name === null)
            return $this->ruleName;

        $this->ruleName = $name;
        return $this;
    }

    /**
     * Route match pattern
     *
     * @param string $pattern Pattern used to match this rule
     * @return object Self
     */
    public function pattern($pattern = null)
    {
        if ($pattern === null)
            return $this->rulePattern;

        $this->rulePattern = $pattern;
        return $this;
    }

    /**
     * Condition callback
     *
     * @param callback $callback Callback function to be used when providing custom route match conditions
     * @throws InvalidArgumentException When supplied argument is not a valid callback
     * @return callback
     */
    public function condition($callback = null)
    {
        if ($callback !== null) {

            if (!is_callable($callback)) {
                throw new InvalidArgumentException(sprintf("Condition provided is not a valid callback. Given (%s)", gettype($callback)));
            }

            $this->conditionCallback = $callback;
            return $this;
        }

        return $this->conditionCallback;
    }


    /**
     * After match callback
     *
     * @param callback $callback Callback function to be used to modify params after a successful match
     * @throws InvalidArgumentException When supplied argument is not a valid callback
     * @return callback
     */
    public function afterMatch($callback = null)
    {
        if ($callback !== null) {

            if (!is_callable($callback)) {
                throw new InvalidArgumentException(sprintf("The after match callback provided is not valid. Given (%s)", gettype($callback)));
            }

            $this->afterMatchCallback = $callback;
            return $this;
        }

        return $this->afterMatchCallback;
    }
}