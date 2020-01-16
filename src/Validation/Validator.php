<?php namespace October\Rain\Validation;

use Illuminate\Validation\Validator as BaseValidator;

class Validator extends BaseValidator
{
    use Concerns\FormatsMessages;
}
