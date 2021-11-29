<?php namespace October\Rain\Validation;

use Illuminate\Contracts\Validation\Rule as RuleContract;

/**
 * Rule is an umbrella class for the Illuminate rule contract.
 *
 * @package october\validation
 * @author Alexey Bobkov, Samuel Georges
 */
abstract class Rule implements RuleContract
{
    /**
     * passes determines if the validation rule passes.
     * @param string $attribute
     * @param mixed $value
     * @return bool
     */
    abstract public function passes($attribute, $value);

    /**
     * message gets the validation error message.
     * @return string
     */
    abstract public function message();

    /**
     * validate callback method.
     * @param string $attribute
     * @param mixed $value
     * @param array $params
     * @return bool
     */
    public function validate($attribute, $value, $params)
    {
        return $this->passes($attribute, $value);
    }

    /**
     * replace defines custom placeholder replacements.
     * @param string $message
     * @param string $attribute
     * @param mixed $rule
     * @param array $params
     * @return string
     */
    public function replace($message, $attribute, $rule, $params)
    {
        return $message;
    }
}
