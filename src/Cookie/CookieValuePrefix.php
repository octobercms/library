<?php namespace October\Rain\Cookie;

/**
 * Helper class to prefix, unprefix, and verify cookie values
 */
class CookieValuePrefix
{
    /**
     * Create a new cookie value prefix for the given cookie name.
     *
     * @param  string $name The name of the cookie
     * @param  string $key The encryption key
     * @return string
     */
    public static function create($name, $key)
    {
        return hash_hmac('sha1', $name . 'v2', $key) . '|';
    }

    /**
     * Remove the cookie value prefix.
     *
     * @param  string  $cookieValue
     * @return string
     */
    public static function remove($cookieValue)
    {
        return substr($cookieValue, 41);
    }

    /**
     * Verify the provided cookie's value
     *
     * @param string $name The name of the cookie
     * @param string $value The decrypted value of the cookie to be verified
     * @param string $key The encryption key used to encrypt the cookie originally
     * @return string|null $verifiedValue The unprefixed value if it passed verification, otherwise null
     */
    public static function getVerifiedValue($name, $value, $key)
    {
        $verifiedValue = null;
        if (starts_with($value, static::create($name, $key))) {
            $verifiedValue = static::remove($value);
        }
        return $verifiedValue;
    }
}
