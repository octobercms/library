<?php

use October\Rain\Validation\Rule;

class PassRule extends Rule
{
    public function passes($attribute, $value)
    {
        return true;
    }

    public function message()
    {
        return 'This should pass';
    }
}
