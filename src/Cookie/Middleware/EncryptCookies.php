<?php namespace October\Rain\Cookie\Middleware;

use Config;
use Illuminate\Contracts\Encryption\Encrypter as EncrypterContract;
use Illuminate\Cookie\Middleware\EncryptCookies as EncryptCookiesBase;

class EncryptCookies extends EncryptCookiesBase
{
    public function __construct(EncrypterContract $encrypter)
    {
        parent::__construct($encrypter);

        // Find unencrypted cookies as specified by the configuration
        $except = Config::get('cookie.unencryptedCookies', []);

        $this->disableFor($except);
    }
}
