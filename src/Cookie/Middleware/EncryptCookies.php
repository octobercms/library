<?php namespace October\Rain\Cookie\Middleware;

use Config;
use Illuminate\Contracts\Encryption\Encrypter as EncrypterContract;

class EncryptCookies extends \Illuminate\Cookie\Middleware\EncryptCookies
{
    /**
     * Indicates if cookies should be serialized.
     *
     * @var bool
     */
    protected static $serialize = true;

    public function __construct(EncrypterContract $encrypter)
    {
        parent::__construct($encrypter);
        $except = Config::get('cookie.unencryptedCookies', []);
        $this->disableFor($except);
    }
}
