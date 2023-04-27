<?php namespace October\Rain\Auth\Concerns;

use Cookie;
use Session;
use Illuminate\Contracts\Auth\Authenticatable;
use October\Rain\Auth\AuthException;

/**
 * HasUser
 *
 * @package october\auth
 * @author Alexey Bobkov, Samuel Georges
 */
trait HasUser
{
    /**
     * createUserModel instance
     */
    public function createUserModel()
    {
        $class = '\\'.ltrim($this->userModel, '\\');
        return new $class();
    }

    /**
     * createRoleModel creates an instance of the role model.
     * @return Models\Role
     */
    public function createRoleModel()
    {
        $class = '\\'.ltrim($this->roleModel, '\\');
        return new $class();
    }

    /**
     * createUserModelQuery prepares a query derived from the user model.
     * @return \October\Rain\Database\Builder $query
     */
    protected function createUserModelQuery()
    {
        $model = $this->createUserModel();
        $query = $model->newQuery();
        $this->extendUserQuery($query);

        return $query;
    }

    /**
     * extendUserQuery used for finding the user.
     * @param \October\Rain\Database\Builder $query
     */
    public function extendUserQuery($query)
    {
    }

    /**
     * hasSession returns true if a user session exists without verifying it.
     */
    public function hasSession(): bool
    {
        return Session::has($this->sessionKey);
    }

    /**
     * hasRemember returns true if the user requested to stay logged in.
     */
    public function hasRemember(): bool
    {
        return Cookie::has($this->sessionKey);
    }

    /**
     * getUser returns the current user, if any.
     * @return Authenticatable|null
     */
    public function getUser()
    {
        if (is_null($this->user)) {
            $this->check();
        }

        return $this->user;
    }

    /**
     * findUserById finds a user by the login value.
     * @param string $id
     * @return Authenticatable|null
     */
    public function findUserById($id)
    {
        $query = $this->createUserModelQuery();

        $user = $query->find($id);

        return $this->validateUserModel($user) ? $user : null;
    }

    /**
     * findUserByLogin finds a user by the login value.
     * @param string $login
     * @return Authenticatable|null
     */
    public function findUserByLogin($login)
    {
        $model = $this->createUserModel();

        $query = $this->createUserModelQuery();

        $user = $query->where($model->getLoginName(), $login)->first();

        return $this->validateUserModel($user) ? $user : null;
    }

    /**
     * findUserByCredentials finds a user by the given credentials.
     * @param array $credentials
     * @throws AuthException
     * @return Models\User
     */
    public function findUserByCredentials(array $credentials)
    {
        $model = $this->createUserModel();
        $loginName = $model->getLoginName();

        if (!array_key_exists($loginName, $credentials)) {
            throw new AuthException("The {$loginName} attribute is required.", 101);
        }

        $query = $this->createUserModelQuery();
        $hashableAttributes = $model->getHashableAttributes();
        $hashedCredentials = [];

        /*
         * Build query from given credentials
         */
        foreach ($credentials as $credential => $value) {
            // All excepted the hashed attributes
            if (in_array($credential, $hashableAttributes)) {
                $hashedCredentials = array_merge($hashedCredentials, [$credential => $value]);
            }
            else {
                $query = $query->where($credential, '=', $value);
            }
        }

        $user = $query->first();
        if (!$this->validateUserModel($user)) {
            throw new AuthException('A user was not found with the given credentials.', 200);
        }

        /*
         * Check the hashed credentials match
         */
        foreach ($hashedCredentials as $credential => $value) {
            if (!$user->checkHashValue($credential, $value)) {
                // Incorrect password
                if ($credential === 'password') {
                    throw new AuthException('A user was found but the password did not match.', 201);
                }

                // User not found
                throw new AuthException('A user was not found with the given credentials.', 200);
            }
        }

        return $user;
    }

    /**
     * validateUserModel perform additional checks on the user model.
     * @param object $user
     * @return bool
     */
    protected function validateUserModel($user)
    {
        return $user instanceof $this->userModel;
    }
}
