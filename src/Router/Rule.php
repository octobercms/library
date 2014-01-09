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
     * Creates a new router rule instance.
     *
     * @param string $name
     * @param string $pattern
     */
    public function __construct($name, $pattern)
    {
        $this->ruleName = $name;
        $this->rulePattern = $pattern;
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