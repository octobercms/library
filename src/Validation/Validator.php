<?php namespace October\Rain\Validation;

use Illuminate\Validation\Validator as ValidatorBase;
use October\Rain\Exception\ValidationException;

/**
 * Validator is a modifier to the base class, it extends validation rules
 * with extra methods for specifying messages and replacements inside the
 * class definition.
 */
class Validator extends ValidatorBase
{
    use \October\Rain\Validation\Concerns\FormatsMessages;

    /**
     * @var string exception to throw upon failure.
     */
    protected $exception = ValidationException::class;
}
