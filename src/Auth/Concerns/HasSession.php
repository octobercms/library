<?php namespace October\Rain\Auth\Concerns;

use Cookie;
use Session;

/**
 * HasSession
 *
 * @package october\auth
 * @author Alexey Bobkov, Samuel Georges
 */
trait HasSession
{
    /**
     * setPersistCodeToSession stores the user persistence in the session and cookie.
     */
    protected function setPersistCodeToSession($user, bool $remember = true, bool $impersonating = false)
    {
        $persistCode = $impersonating && $user->persist_code
            ? $user->persist_code
            : $user->getPersistCode();

        $toPersist = [$user->getKey(), $persistCode];

        Session::put($this->sessionKey, $toPersist);

        if ($remember) {
            Cookie::queue(Cookie::forever($this->sessionKey, json_encode($toPersist)));
        }
    }

    /**
     * getPersistCodeFromSession will return the user ID and persist token from the session.
     * The resulting array will contain the user ID and persistence code [id, code] or null.
     */
    protected function getPersistCodeFromSession(bool $isChecking = true): ?array
    {
        // Check session first, followed by cookie
        if ($sessionArray = Session::get($this->sessionKey)) {
            $userArray = $sessionArray;
        }
        elseif ($cookieArray = Cookie::get($this->sessionKey)) {
            if ($isChecking) {
                $this->viaRemember = true;
            }
            $userArray = @json_decode($cookieArray, true);
        }
        else {
            return null;
        }

        // Check supplied session/cookie is an array (user id, persist code)
        if (!is_array($userArray) || count($userArray) !== 2) {
            return null;
        }

        return $userArray;
    }
}
