<?php namespace October\Rain\Validation\Concerns;

use Illuminate\Support\Str;

/**
 * FormatsMessages is a modifier to the base trait
 *
 * @see \Illuminate\Validation\Concerns\FormatsMessages
 */
trait FormatsMessages
{
    /**
     * getMessage message for a validation attribute and rule.
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

        // Apply fallback message from extension class, if one exists.
        // This is custom logic from the parent class.
        if ($this->hasExtensionMethod($lowerRule, 'message')) {
            return $this->callExtensionMethod($lowerRule, 'message');
        }

        // If the rule being validated is a "size" rule, we will need to gather the
        // specific error message for the type of attribute being validated such
        // as a number, file or string which all have different message types.
        if (in_array($rule, $this->sizeRules)) {
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
     * makeReplacements replace all error message place-holders with actual values.
     * @param  string  $message
     * @param  string  $attribute
     * @param  string  $rule
     * @param  array  $parameters
     * @return string
     */
    public function makeReplacements($message, $attribute, $rule, $parameters)
    {
        $message = $this->replaceAttributePlaceholder(
            $message, $this->getDisplayableAttribute($attribute)
        );

        $lowerRule = Str::snake($rule);

        $message = $this->replaceInputPlaceholder($message, $attribute);

        if (isset($this->replacers[$lowerRule])) {
            return $this->callReplacer($message, $attribute, $lowerRule, $parameters, $this);
        }
        elseif (method_exists($this, $replacer = "replace{$rule}")) {
            return $this->$replacer($message, $attribute, $rule, $parameters);
        }

        // Apply fallback replacer from extension class, if one exists.
        // This is custom logic from the parent class.
        if ($this->hasExtensionMethod($lowerRule, 'replace')) {
            return $this->callExtensionMethod($lowerRule, 'replace', [$message, $attribute, $lowerRule, $parameters]);
        }

        return $message;
    }

    /**
     * hasExtensionMethod determines if an extended rule has a given method.
     */
    protected function hasExtensionMethod(string $rule, string $methodName): bool
    {
        if (!isset($this->extensions[$rule]) || !is_string($this->extensions[$rule])) {
            return false;
        }

        [$class, $method] = Str::parseCallback($this->extensions[$rule]);

        if (!method_exists($class, $methodName)) {
            return false;
        }

        return true;
    }

    /**
     * callExtensionMethod calls a method for an extended rule and returns the result as a string.
     */
    protected function callExtensionMethod(string $rule, string $methodName, array $args = []): string
    {
        [$class, $method] = Str::parseCallback($this->extensions[$rule]);

        return (string) call_user_func_array([$this->container->make($class), $methodName], $args);
    }
}
