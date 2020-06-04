<?php namespace October\Rain\Validation\Concerns;

use Illuminate\Support\Str;

trait FormatsMessages
{
    /**
     * Get the validation message for an attribute and rule.
     *
     * @param  string  $attribute
     * @param  string  $rule
     * @return string
     */
    protected function getMessage($attribute, $rule)
    {
        $inlineMessage = $this->getInlineMessage($attribute, $rule);

        // First we will retrieve the custom message for the validation rule if one
        // exists. If a custom validation message is being used we'll return the
        // custom message, otherwise we'll keep searching for a valid message.
        if (!is_null($inlineMessage)) {
            return $inlineMessage;
        }

        $lowerRule = Str::snake($rule);

        $customMessage = $this->getCustomMessageFromTranslator(
            $customKey = "validation.custom.{$attribute}.{$lowerRule}"
        );

        // First we check for a custom defined validation message for the attribute
        // and rule. This allows the developer to specify specific messages for
        // only some attributes and rules that need to get specially formed.
        if ($customMessage !== $customKey) {
            return $customMessage;
        }

        // Next, we'll check if the rule is an extension, and the extended class has
        // a fallback message within the class itself.
        elseif ($this->hasMessageInExtension($lowerRule)) {
            return $this->getMessageInExtension($lowerRule);
        }

        // If the rule being validated is a "size" rule, we will need to gather the
        // specific error message for the type of attribute being validated such
        // as a number, file or string which all have different message types.
        elseif (in_array($rule, $this->sizeRules)) {
            return $this->getSizeMessage($attribute, $rule);
        }

        // Finally, if no developer specified messages have been set, and no other
        // special messages apply for this rule, we will just pull the default
        // messages out of the translator service for this validation rule.
        $key = "validation.{$lowerRule}";

        if ($key != ($value = $this->translator->get($key))) {
            return $value;
        }

        return $this->getFromLocalArray(
            $attribute,
            $lowerRule,
            $this->fallbackMessages
        ) ?: $key;
    }

    /**
     * Determines if an extended rule has a `message()` method that provides a fallback message.
     *
     * @param string $rule
     * @return boolean
     */
    protected function hasMessageInExtension($rule)
    {
        if (!isset($this->extensions[$rule]) || !is_string($this->extensions[$rule])) {
            return false;
        }

        [$class, $method] = Str::parseCallback($this->extensions[$rule]);

        if (!method_exists($class, 'message')) {
            return false;
        }

        return true;
    }

    /**
     * Calls the `message()` method for an extended rule and returns the result as a string.
     *
     * @param string $rule
     * @return string
     */
    protected function getMessageInExtension($rule)
    {
        [$class, $method] = Str::parseCallback($this->extensions[$rule]);

        return (string) call_user_func_array([$this->container->make($class), 'message'], []);
    }
}
