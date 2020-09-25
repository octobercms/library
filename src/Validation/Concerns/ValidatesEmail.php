<?php namespace October\Rain\Validation\Concerns;

use Egulias\EmailValidator\EmailValidator;
use Egulias\EmailValidator\Validation\DNSCheckValidation;
use Egulias\EmailValidator\Validation\MultipleValidationWithAnd;
use Egulias\EmailValidator\Validation\NoRFCWarningsValidation;
use Egulias\EmailValidator\Validation\RFCValidation;
use Egulias\EmailValidator\Validation\SpoofCheckValidation;
use Illuminate\Validation\Concerns\FilterEmailValidation;

trait ValidatesEmail
{
    /**
     * Validate that an attribute is a valid e-mail address.
     *
     * Laravel 5.8 and above, by default, use the RFCValidation provider as the default validation. To keep
     * backwards compatibility, we intend to use the FilterEmailValidation provider.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @param  array  $parameters
     * @return bool
     */
    public function validateEmail($attribute, $value, $parameters)
    {
        if (!is_string($value) && !(is_object($value) && method_exists($value, '__toString'))) {
            return false;
        }

        $validations = collect($parameters)
            ->unique()
            ->map(function ($validation) {
                if ($validation === 'rfc') {
                    return new RFCValidation();
                } elseif ($validation === 'strict') {
                    return new NoRFCWarningsValidation();
                } elseif ($validation === 'dns') {
                    return new DNSCheckValidation();
                } elseif ($validation === 'spoof') {
                    return new SpoofCheckValidation();
                } elseif ($validation === 'filter') {
                    return new FilterEmailValidation();
                }
            })
            ->values()
            ->all() ?: [new FilterEmailValidation()];

        return (new EmailValidator)->isValid($value, new MultipleValidationWithAnd($validations));
    }
}
