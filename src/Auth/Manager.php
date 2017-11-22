<?php namespace October\Rain\Auth;

use Cookie;
use Session;
use Request;

/**
 * Authentication manager
 */
class Manager
{
    use \October\Rain\Support\Traits\Singleton;

    protected $user;

    protected $throttle = [];

    protected $userModel = 'October\Rain\Auth\Models\User';

    protected $groupModel = 'October\Rain\Auth\Models\Group';

    protected $throttleModel = 'October\Rain\Auth\Models\Throttle';

    protected $useThrottle = true;

    protected $requireActivation = true;

    protected $sessionKey = 'october_auth';

    public $ipAddress = '0.0.0.0';

    protected function init()
    {
        $this->ipAddress = Request::ip();
    }

    //
    // User
    //

    /**
     * Creates a new instance of the user model
     */
    public function createUserModel()
    {
        $class = '\\'.ltrim($this->userModel, '\\');
        $user = new $class();
        return $user;
    }

    /**
     * Prepares a query derived from the user model.
     */
    protected function createUserModelQuery()
    {
        $model = $this->createUserModel();
        $query = $model->newQuery();
        $this->extendUserQuery($query);
        return $query;
    }

    /**
     * Extend the query used for finding the user.
     * @param \October\Rain\Database\Builder $query
     * @return void
     */
    public function extendUserQuery($query)
    {
    }

    /**
     * Registers a user by giving the required credentials
     * and an optional flag for whether to activate the user.
     *
     * @param array $credentials
     * @param bool $activate
     * @return Models\User
     */
    public function register(array $credentials, $activate = false)
    {
        $user = $this->createUserModel();
        $user->fill($credentials);
        $user->save();

        if ($activate) {
            $user->attemptActivation($user->getActivationCode());
        }

        // Prevents revalidation of the password field
        // on subsequent saves to this model object
        $user->password = null;

        return $this->user = $user;
    }

    /**
     * Sets the user
     */
    public function setUser($user)
    {
        $this->user = $user;
    }

    /**
     * Returns the current user, if any.
     */
    public function getUser()
    {
        if (is_null($this->user)) {
            $this->check();
        }

        return $this->user;
    }

    /**
     * Finds a user by the login value.
     * @param string $id
     */
    public function findUserById($id)
    {
        $query = $this->createUserModelQuery();
        $user = $query->find($id);
        return $user ?: null;
    }

    /**
     * Finds a user by the login value.
     * @param string $login
     */
    public function findUserByLogin($login)
    {
        $model = $this->createUserModel();
        $query = $this->createUserModelQuery();
        $user = $query->where($model->getLoginName(), $login)->first();
        return $user ?: null;
    }

    /**
     * Finds a user by the given credentials.
     */
    public function findUserByCredentials(array $credentials)
    {
        $model = $this->createUserModel();
        $loginName = $model->getLoginName();

        if (!array_key_exists($loginName, $credentials)) {
            throw new AuthException(sprintf('Login attribute "%s" was not provided.', $loginName));
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

        if (!$user = $query->first()) {
            throw new AuthException('A user was not found with the given credentials.');
        }

        /*
         * Check the hashed credentials match
         */
        foreach ($hashedCredentials as $credential => $value) {

            if (!$user->checkHashValue($credential, $value)) {
                // Incorrect password
                if ($credential == 'password') {
                    throw new AuthException(sprintf(
                        'A user was found to match all plain text credentials however hashed credential "%s" did not match.', $credential
                    ));
                }

                // User not found
                throw new AuthException('A user was not found with the given credentials.');
            }
        }

        return $user;
    }

    //
    // Throttle
    //

    /**
     * Creates an instance of the throttle model
     */
    public function createThrottleModel()
    {
        $class = '\\'.ltrim($this->throttleModel, '\\');
        $user = new $class();
        return $user;
    }

    /**
     * Find a throttle record by login and ip address
     */
    public function findThrottleByLogin($loginName, $ipAddress)
    {
        $user = $this->findUserByLogin($loginName);
        if (!$user) {
            throw new AuthException("A user was not found with the given credentials.");
        }

        $userId = $user->getKey();
        return $this->findThrottleByUserId($userId, $ipAddress);
    }

    /**
     * Find a throttle record by user id and ip address
     */
    public function findThrottleByUserId($userId, $ipAddress = null)
    {
        $cacheKey = md5($userId.$ipAddress);
        if (isset($this->throttle[$cacheKey])) {
            return $this->throttle[$cacheKey];
        }

        $model = $this->createThrottleModel();
        $query = $model->where('user_id', '=', $userId);

        if ($ipAddress) {
            $query->where(function($query) use ($ipAddress) {
                $query->where('ip_address', '=', $ipAddress);
                $query->orWhere('ip_address', '=', null);
            });
        }

        if (!$throttle = $query->first()) {
            $throttle = $this->createThrottleModel();
            $throttle->user_id = $userId;
            if ($ipAddress) {
                $throttle->ip_address = $ipAddress;
            }

            $throttle->save();
        }

        return $this->throttle[$cacheKey] = $throttle;
    }

    //
    // Business Logic
    //

    /**
     * Attempts to authenticate the given user according to the passed credentials.
     *
     * @param array $credentials The user login details
     * @param bool $remember Store a non-expire cookie for the user
     */
    public function authenticate(array $credentials, $remember = true)
    {
        /*
         * Default to the login name field or fallback to a hard-coded 'login' value
         */
        $loginName = $this->createUserModel()->getLoginName();
        $loginCredentialKey = (isset($credentials[$loginName])) ? $loginName : 'login';

        if (empty($credentials[$loginCredentialKey])) {
            throw new AuthException(sprintf('The "%s" attribute is required.', $loginCredentialKey));
        }

        if (empty($credentials['password'])) {
            throw new AuthException('The password attribute is required.');
        }

        /*
         * If the fallback 'login' was provided and did not match the necessary
         * login name, swap it over
         */
        if ($loginCredentialKey !== $loginName) {
            $credentials[$loginName] = $credentials[$loginCredentialKey];
            unset($credentials[$loginCredentialKey]);
        }

        /*
         * If throttling is enabled, check they are not locked out first and foremost.
         */
        if ($this->useThrottle) {
            $throttle = $this->findThrottleByLogin($credentials[$loginName], $this->ipAddress);
            $throttle->check();
        }

        /*
         * Look up the user by authentication credentials.
         */
        try {
            $user = $this->findUserByCredentials($credentials);
        }
        catch (AuthException $ex) {
            if ($this->useThrottle) {
                $throttle->addLoginAttempt();
            }

            throw $ex;
        }

        if ($this->useThrottle) {
            $throttle->clearLoginAttempts();
        }

        $user->clearResetPassword();
        $this->login($user, $remember);

        return $this->user;
    }

    /**
     * Check to see if the user is logged in and activated, and hasn't been banned or suspended.
     *
     * @return bool
     */
    public function check()
    {
        if (is_null($this->user)) {

            /*
             * Check session first, follow by cookie
             */
            if (
                !($userArray = Session::get($this->sessionKey)) &&
                !($userArray = Cookie::get($this->sessionKey))
            ) {
                return false;
            }

            /*
             * Check supplied session/cookie is an array (user id, persist code)
             */
            if (!is_array($userArray) || count($userArray) !== 2) {
                return false;
            }

            list($id, $persistCode) = $userArray;

            /*
             * Look up user
             */
            if (!$user = $this->createUserModel()->find($id)) {
                return false;
            }

            /*
             * Confirm the persistence code is valid, otherwise reject
             */
            if (!$user->checkPersistCode($persistCode)) {
                return false;
            }

            /*
             * Pass
             */
            $this->user = $user;
        }

        /*
         * Check cached user is activated
         */
        if (!($user = $this->getUser()) || ($this->requireActivation && !$user->is_activated)) {
            return false;
        }

        /*
         * Throttle check
         */
        if ($this->useThrottle) {
            $throttle = $this->findThrottleByUserId($user->getKey());

            if ($throttle->is_banned || $throttle->checkSuspended()) {
                $this->logout();
                return false;
            }
        }

        return true;
    }

    /**
     * Logs in the given user and sets properties
     * in the session.
     */
    public function login($user, $remember = true)
    {
        /*
         * Fire the 'beforeLogin' event
         */
        $user->beforeLogin();

        /*
         * Activation is required, user not activated
         */
        if ($this->requireActivation && !$user->is_activated) {
            $login = $user->getLogin();
            throw new AuthException(sprintf(
                'Cannot login user "%s" as they are not activated.', $login
            ));
        }

        $this->user = $user;

        /*
         * Create session/cookie data to persist the session
         */
        $toPersist = [$user->getKey(), $user->getPersistCode()];
        Session::put($this->sessionKey, $toPersist);

        if ($remember) {
            Cookie::queue(Cookie::forever($this->sessionKey, $toPersist));
        }

        /*
         * Fire the 'afterLogin' event
         */
        $user->afterLogin();
    }

    /**
     * Logs the current user out.
     */
    public function logout()
    {
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

        Session::forget($this->sessionKey);
        Cookie::queue(Cookie::forget($this->sessionKey));
    }

    //
    // Impersonation
    //

    /**
     * Impersonates the given user and sets properties
     * in the session but not the cookie.
     */
    public function impersonate($user)
    {
        $oldSession = Session::get($this->sessionKey);

        $this->login($user, false);

        if (!$this->isImpersonator()) {
            Session::put($this->sessionKey.'_impersonate', $oldSession);
        }
    }

    public function stopImpersonate()
    {
        $oldSession = Session::pull($this->sessionKey.'_impersonate');

        Session::put($this->sessionKey, $oldSession);
    }

    public function isImpersonator()
    {
        return Session::has($this->sessionKey.'_impersonate');
    }

    public function getImpersonator()
    {
        $impersonateArray = Session::get($this->sessionKey.'_impersonate');

        /*
         * Check supplied session/cookie is an array (user id, persist code)
         */
        if (!is_array($impersonateArray) || count($impersonateArray) !== 2) {
            return false;
        }

        $id = reset($impersonateArray);

        return $this->createUserModel()->find($id);
    }
}
