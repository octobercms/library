<?php namespace October\Rain\Validation;

use Illuminate\Validation\Factory as FactoryBase;

/**
 * Factory resolves to the Rain flavored validator.
 */
class Factory extends FactoryBase
{
    /**
     * resolve
     */
    protected function resolve(array $data, array $rules, array $messages, array $customAttributes)
    {
        if (is_null($this->resolver)) {
            return new Validator($this->translator, $data, $rules, $messages, $customAttributes);
        }

        return call_user_func($this->resolver, $this->translator, $data, $rules, $messages, $customAttributes);
    }
}
