<?php namespace October\Rain\Cookie\Middleware;

use Config;
use Illuminate\Contracts\Encryption\Encrypter as EncrypterContract;

class EncryptCookies extends \Illuminate\Cookie\Middleware\EncryptCookies
{
    public function __construct(EncrypterContract $encrypter)
    {
        parent::__construct($encrypter);
        $except = Config::get('cookie.unencrypted_cookies');
        $this->disableFor($except);
    }
}
