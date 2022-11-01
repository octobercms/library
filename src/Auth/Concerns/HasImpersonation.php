<?php namespace October\Rain\Auth\Concerns;

use Session;
use Illuminate\Contracts\Auth\Authenticatable;

/**
 * HasImpersonation
 *
 * @package october\auth
 * @author Alexey Bobkov, Samuel Georges
 */
trait HasImpersonation
{
    /**
     * impersonate the given user and sets properties in the session but not the cookie.
     */
    public function impersonate($user)
    {
        // Determine previous user
        $userArray = $this->getPersistCodeFromSession(false);
        $oldUserId = $userArray ? $userArray[0] : null;

        /**
         * @event model.auth.beforeImpersonate
         *
         * Example usage:
         *
         *     $model->bindEvent('model.auth.beforeImpersonate', function (\October\Rain\Database\Model|null $oldUser) use (\October\Rain\Database\Model $model) {
         *         traceLog($oldUser->full_name . ' is now impersonating ' . $model->full_name);
         *     });
         *
         */
        $oldUser = $oldUserId ? $this->findUserById($oldUserId) : null;
        $user->fireEvent('model.auth.beforeImpersonate', [$oldUser]);

        // Replace session with impersonated user
        $this->setPersistCodeToSession($user, false, true);

        // If this is the first time impersonating, capture the original user
        if (!$this->isImpersonator()) {
            Session::put($this->sessionKey.'_impersonate', $oldUserId ?: 'NaN');
        }
    }

    /**
     * stopImpersonate stops the current session being impersonated and
     * attempts to authenticate as the impersonator again.
     */
    public function stopImpersonate()
    {
        // Determine current and previous user
        $userArray = $this->getPersistCodeFromSession(false);
        $currentUserId = $userArray ? $userArray[0] : null;
        $oldUser = $this->getImpersonator();

        if ($currentUserId && ($currentUser = $this->findUserById($currentUserId))) {
            /**
             * @event model.auth.afterImpersonate
             *
             * Example usage:
             *
             *     $model->bindEvent('model.auth.afterImpersonate', function (\October\Rain\Database\Model|null $oldUser) use (\October\Rain\Database\Model $model) {
             *         traceLog($oldUser->full_name . ' has stopped impersonating ' . $model->full_name);
             *     });
             *
             */
            $currentUser->fireEvent('model.auth.afterImpersonate', [$oldUser]);
        }

        // Restore previous user, if possible
        if ($oldUser) {
            $this->setPersistCodeToSession($oldUser, false, true);
        }
        else {
            Session::forget($this->sessionKey);
        }

        Session::forget($this->sessionKey.'_impersonate');
    }

    /**
     * getRealUser gets the "real" user to bypass impersonation.
     * @return Authenticatable|null
     */
    public function getRealUser()
    {
        if ($user = $this->getImpersonator()) {
            return $user;
        }

        return $this->getUser();
    }

    /**
     * isImpersonator checks to see if the current session is being impersonated.
     * @return bool
     */
    public function isImpersonator()
    {
        return Session::has($this->sessionKey.'_impersonate');
    }

    /**
     * getImpersonator gets the original user doing the impersonation
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
    public function getImpersonator()
    {
        if (!Session::has($this->sessionKey.'_impersonate')) {
            return null;
        }

        $oldUserId = Session::get($this->sessionKey.'_impersonate');
        if ((!is_string($oldUserId) && !is_int($oldUserId)) || $oldUserId === 'NaN') {
            return null;
        }

        return $this->createUserModel()->find($oldUserId);
    }

    /**
     * impersonateRole will impersonate a role for the current user
     */
    public function impersonateRole($role)
    {
        Session::put($this->sessionKey.'_impersonate_role', $role->getKey());
    }

    /**
     * isRoleImpersonator
     */
    public function isRoleImpersonator(): bool
    {
        return !empty(Session::has($this->sessionKey.'_impersonate_role'));
    }

    /**
     * stopImpersonateRole will stop role impersonation
     */
    public function stopImpersonateRole()
    {
        Session::forget($this->sessionKey.'_impersonate_role');
    }

    /**
     * applyRoleImpersonation tells the user model to impersonate the role
     */
    protected function applyRoleImpersonation($user)
    {
        $roleId = Session::get($this->sessionKey.'_impersonate_role');

        if ($role = $this->createRoleModel()->find($roleId)) {
            $user->setRoleImpersonation($role);
        }
    }
}
