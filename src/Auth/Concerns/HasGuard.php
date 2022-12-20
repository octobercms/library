<?php namespace October\Rain\Auth\Concerns;

use Illuminate\Contracts\Auth\Authenticatable;

/**
 * HasGuard defines all methods to satisfy the Laravel contract
 *
 * @package october\auth
 * @author Alexey Bobkov, Samuel Georges
 */
trait HasGuard
{
    /**
     * check to see if the user is logged in and activated, and hasn't been banned or suspended.
     * @return bool
     */
    public function check()
    {
        if ($this->checkCache !== null) {
            return $this->checkCache;
        }

        if (is_null($this->user)) {
            // Find persistence code
            $userArray = $this->getPersistCodeFromSession();
            if (!$userArray) {
                return false;
            }

            [$id, $persistCode] = $userArray;

            // Look up user
            if (!$user = $this->findUserById($id)) {
                return $this->checkCache = false;
            }

            // Confirm the persistence code is valid, otherwise reject
            if (!$user->checkPersistCode($persistCode)) {
                return $this->checkCache = false;
            }

            // Pass
            $this->user = $user;
        }

        // Check cached user is activated
        if (!($user = $this->getUser()) || ($this->requireActivation && !$user->is_activated)) {
            return false;
        }

        // Throttle check
        if ($this->useThrottle) {
            $throttle = $this->findThrottleByUserId($user->getKey(), $this->ipAddress);

            if ($throttle->is_banned || $throttle->checkSuspended()) {
                $this->logout();
                return false;
            }
        }

        // Role impersonation
        if ($this->isRoleImpersonator()) {
            $this->applyRoleImpersonation($this->user);
        }

        return true;
    }

    /**
     * guest determines if the current user is a guest.
     * @return bool
     */
    public function guest()
    {
        return false;
    }

    /**
     * user will return the currently authenticated user.
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
    public function user()
    {
        return $this->getUser();
    }

    /**
     * id for the currently authenticated user.
     * @return int|null
     */
    public function id()
    {
        if ($user = $this->getUser()) {
            return $user->getAuthIdentifier();
        }

        return null;
    }

    /**
     * validate a user's credentials.
     * @return bool
     */
    public function validate(array $credentials = [])
    {
        return !!$this->validateInternal($credentials);
    }

    /**
     * hasUser determines if the guard has a user instance.
     * @return bool
     */
    public function hasUser()
    {
        return !is_null($this->user);
    }

    /**
     * setUser will set the current user.
     */
    public function setUser(Authenticatable $user)
    {
        $this->user = $user;
    }
}
