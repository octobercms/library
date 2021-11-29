<?php namespace October\Rain\Validation;

use Illuminate\Validation\Validator as ValidatorBase;

/**
 * Validator is a modifier to the base class
 */
class Validator extends ValidatorBase
{
    use \October\Rain\Validation\Concerns\FormatsMessages;
}
