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

    /**
     * Shift gracefully to unserialized cookies
     * @todo Remove entire method if year >= 2021 or build >= 475
     */
    protected function decryptCookie($name, $cookie)
    {
        if (is_array($cookie)) {
            return $this->decryptArray($cookie);
        }

        try {
            $result = $this->encrypter->decrypt($cookie, true);
        }
        catch (\Exception $ex) {
            $result = $this->encrypter->decrypt($cookie, false);
        }

        return $result;
    }

    /**
     * Shift gracefully to unserialized cookies
     * @todo Remove entire method if year >= 2021 or build >= 475
     */
    protected function decryptArray(array $cookie)
    {
        $decrypted = [];

        foreach ($cookie as $key => $value) {
            if (is_string($value)) {
                try {
                    $decrypted[$key] = $this->encrypter->decrypt($value, true);
                }
                catch (\Exception $ex) {
                    $decrypted[$key] = $this->encrypter->decrypt($value, false);
                }
            }
        }

        return $decrypted;
    }
}
