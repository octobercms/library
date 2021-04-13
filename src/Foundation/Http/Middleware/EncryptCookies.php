<?php namespace October\Rain\Foundation\Http\Middleware;

use Config;
use Illuminate\Contracts\Encryption\Encrypter as EncrypterContract;
use Illuminate\Cookie\Middleware\EncryptCookies as EncryptCookiesBase;

class EncryptCookies extends EncryptCookiesBase
{
    public function __construct(EncrypterContract $encrypter)
    {
        parent::__construct($encrypter);

        // Cookies that should not be encrypted
        $except = is_array($labels = Config::get('system.unencrypt_cookies'))
            ? $labels
            : (array) json_decode($labels, true);

        $this->disableFor($except);
    }
}
