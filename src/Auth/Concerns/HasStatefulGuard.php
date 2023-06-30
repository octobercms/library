<?php namespace October\Rain\Auth\Concerns;

use Cookie;
use Session;
use Illuminate\Contracts\Auth\Authenticatable;
use October\Rain\Auth\AuthException;

/**
 * HasStatefulGuard defines all methods to satisfy the Laravel contract
 *
 * @package october\auth
 * @author Alexey Bobkov, Samuel Georges
 */
trait HasStatefulGuard
{
    /**
     * attempt to authenticate a user using the given credentials.
     *
     * @param array $credentials The user login details
     * @param bool $remember Store a non-expire cookie for the user
     * @throws AuthException If authentication fails
     * @return Models\User The successfully logged in user
     */
    public function attempt(array $credentials = [], $remember = false)
    {
        return !!$this->authenticate($credentials, $remember);
    }

    /**
     * once logs a user into the application without sessions or cookies.
     * @param  array  $credentials
     * @return bool
     */
    public function once(array $credentials = [])
    {
        $this->useSession = false;

        $user = $this->authenticate($credentials);

        $this->useSession = true;

        return !!$user;
    }

    /**
     * login the given user and sets properties in the session.
     * @throws AuthException If the user is not activated and $this->requireActivation = true
     */
    public function login(Authenticatable $user, $remember = true)
    {
        // Fire the 'beforeLogin' event
        $user->beforeLogin();

        // Activation is required, user not activated
        if ($this->requireActivation && !$user->is_activated) {
            throw new AuthException('Cannot login user since they are not activated.', 300);
        }

        $this->user = $user;

        // Create session/cookie data to persist the session
        if ($this->useSession) {
            $this->setPersistCodeToSession($user, $remember);
        }

        // Fire the 'afterLogin' event
        $user->afterLogin();
    }

    /**
     * loginUsingId logs the given user ID into the application.
     * @param  mixed  $id
     * @param  bool   $remember
     * @return \Illuminate\Contracts\Auth\Authenticatable|bool
     */
    public function loginUsingId($id, $remember = false)
    {
        if (!is_null($user = $this->findUserById($id))) {
            $this->login($user, $remember);

            return $user;
        }

        return false;
    }

    /**
     * onceUsingId logs the given user ID into the application without sessions or cookies.
     * @param  mixed  $id
     * @return \Illuminate\Contracts\Auth\Authenticatable|false
     */
    public function onceUsingId($id)
    {
        if (!is_null($user = $this->findUserById($id))) {
            $this->setUser($user);

            return $user;
        }

        return false;
    }

    /**
     * viaRemember determines if the user was authenticated via "remember me" cookie.
     * @return bool
     */
    public function viaRemember()
    {
        return $this->viaRemember;
    }

    /**
     * logout logs the current user out.
     */
    public function logout()
    {
        // Initialize the current auth session before trying to remove it
        if (is_null($this->user) && !$this->check()) {
            return;
        }

        if ($this->isImpersonator()) {
            $this->user = $this->getImpersonator();
            $this->stopImpersonate();
            return;
        }

        if ($this->user) {
            $this->user->setRememberToken(null);
            $this->user->forceSave();
        }

        $this->user = null;

        Session::invalidate();
        Cookie::queue(Cookie::forget($this->sessionKey));
    }
}
