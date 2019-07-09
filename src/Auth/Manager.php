<?php namespace October\Rain\Auth;

use Cookie;
use Session;
use Request;
use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Authentication manager
 */
class Manager implements \Illuminate\Contracts\Auth\StatefulGuard
{
    use \October\Rain\Support\Traits\Singleton;

    /**
     * @var Models\User The currently logged in user
     */
    protected $user;

    /**
     * @var array In memory throttle cache [md5($userId.$ipAddress) => $this->throttleModel]
     */
    protected $throttle = [];

    /**
     * @var string User Model Class
     */
    protected $userModel = Models\User::class;

    /**
     * @var string User Group Model Class
     */
    protected $groupModel = Models\Group::class;

    /**
     * @var string Throttle Model Class
     */
    protected $throttleModel = Models\Throttle::class;

    /**
     * @var bool Flag to enable login throttling
     */
    protected $useThrottle = true;
  
    /**
     * @var bool Internal flag to toggle using the session for the current authentication request
     */
    protected $useSession = true;

    /**
     * @var bool Flag to require users to be activated to login
     */
    protected $requireActivation = true;

    /**
     * @var string Key to store the auth session data in
     */
    protected $sessionKey = 'october_auth';

    /**
     * @var bool Indicates if the user was authenticated via a recaller cookie.
     */
    protected $viaRemember = false;

    /**
     * @var string The IP address of this request
     */
    public $ipAddress = '0.0.0.0';

    /**
     * Initializes the singleton
     */
    protected function init()
    {
        $this->ipAddress = Request::ip();
    }

    //
    // User
    //

    /**
     * Creates a new instance of the user model
     *
     * @return Models\User
     */
    public function createUserModel()
    {
        $class = '\\'.ltrim($this->userModel, '\\');
        return new $class();
    }

    /**
     * Prepares a query derived from the user model.
     *
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
     * Extend the query used for finding the user.
     * @param \October\Rain\Database\Builder $query
     * @return void
     */
    public function extendUserQuery($query)
    {
    }

    /**
     * Registers a user with the provided credentials with optional flags
     * for activating the newly created user and automatically logging them in
     *
     * @param array $credentials
     * @param bool $activate
     * @param bool $autoLogin
     * @return Models\User
     */
    public function register(array $credentials, $activate = false, $autoLogin = true)
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

        if ($autoLogin) {
            $this->user = $user;
        }

        return $user;
    }

    /**
     * Sets the user
     */
    public function setUser(Authenticatable $user)
    {
        $this->user = $user;
    }

    /**
     * Returns the current user, if any.
     *
     * @return mixed (Models\User || null)
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
     *
     * @param string $id
     * @return mixed (Models\User || null)
     */
    public function findUserById($id)
    {
        $query = $this->createUserModelQuery();

        $user = $query->find($id);

        return $this->validateUserModel($user) ? $user : null;
    }

    /**
     * Finds a user by the login value.
     *
     * @param string $login
     * @return mixed (Models\User || null)
     */
    public function findUserByLogin($login)
    {
        $model = $this->createUserModel();

        $query = $this->createUserModelQuery();

        $user = $query->where($model->getLoginName(), $login)->first();

        return $this->validateUserModel($user) ? $user : null;
    }

    /**
     * Finds a user by the given credentials.
     *
     * @param array $credentials The credentials to find a user by
     * @throws AuthException If the credentials are invalid
     * @return Models\User The requested user
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

        $user = $query->first();
        if (!$this->validateUserModel($user)) {
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

    /**
     * Perform additional checks on the user model.
     *
     * @param $user
     * @return boolean
     */
    protected function validateUserModel($user)
    {
        return $user instanceof $this->userModel;
    }

    //
    // Throttle
    //

    /**
     * Creates an instance of the throttle model
     *
     * @return Models\Throttle
     */
    public function createThrottleModel()
    {
        $class = '\\'.ltrim($this->throttleModel, '\\');
        return new $class();
    }

    /**
     * Find a throttle record by login and ip address
     *
     * @param string $loginName
     * @param string $ipAddress
     * @return Models\Throttle
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
     *
     * @param integer $userId
     * @param string $ipAddress
     * @return Models\Throttle
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
     * Attempt to authenticate a user using the given credentials.
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
     * Validate a user's credentials.
     *
     * @param  array  $credentials
     * @return bool
     */
    public function validate(array $credentials = [])
    {
        return !!$this->validateInternal($credentials);
    }

    /**
     * Validate a user's credentials, method used internally.
     *
     * @param  array  $credentials
     * @return User
     */
    protected function validateInternal(array $credentials = [])
    {
        /*
         * Default to the login name field or fallback to a hard-coded 'login' value
         */
        $loginName = $this->createUserModel()->getLoginName();
        $loginCredentialKey = isset($credentials[$loginName]) ? $loginName : 'login';

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

        return $user;
    }

    /**
     * Attempts to authenticate the given user according to the passed credentials.
     *
     * @param array $credentials The user login details
     * @param bool $remember Store a non-expire cookie for the user
     */
    public function authenticate(array $credentials, $remember = true)
    {
        $user = $this->validateInternal($credentials);

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
            if ($sessionArray = Session::get($this->sessionKey)) {
                $userArray = $sessionArray;
            }
            elseif ($cookieArray = Cookie::get($this->sessionKey)) {
                $this->viaRemember = true;
                $userArray = $cookieArray;
            }
            else {
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
            if (!$user = $this->findUserById($id)) {
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
            $throttle = $this->findThrottleByUserId($user->getKey(), $this->ipAddress);

            if ($throttle->is_banned || $throttle->checkSuspended()) {
                $this->logout();
                return false;
            }
        }

        return true;
    }

    /**
     * Determine if the current user is a guest.
     *
     * @return bool
     */
    public function guest()
    {
        return false;
    }

    /**
     * Get the currently authenticated user.
     *
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
    public function user()
    {
        return $this->getUser();
    }

    /**
     * Get the ID for the currently authenticated user.
     *
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
     * Log a user into the application without sessions or cookies.
     *
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
     * Log the given user ID into the application without sessions or cookies.
     *
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
     * Logs in the given user and sets properties
     * in the session.
     * @throws AuthException If the user is not activated and $this->requireActivation = true
     */
    public function login(Authenticatable $user, $remember = true)
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
        if ($this->useSession) {
            $toPersist = [$user->getKey(), $user->getPersistCode()];
            Session::put($this->sessionKey, $toPersist);

            if ($remember) {
                Cookie::queue(Cookie::forever($this->sessionKey, $toPersist));
            }
        }

        /*
         * Fire the 'afterLogin' event
         */
        $user->afterLogin();
    }

    /**
     * Log the given user ID into the application.
     *
     * @param  mixed  $id
     * @param  bool   $remember
     * @return \Illuminate\Contracts\Auth\Authenticatable
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
     * Determine if the user was authenticated via "remember me" cookie.
     *
     * @return bool
     */
    public function viaRemember()
    {
        return $this->viaRemember;
    }

    /**
     * Logs the current user out.
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

        Session::flush();
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
        $oldUser = !empty($oldSession[0]) ? $this->findUserById($oldSession[0]) : false;

        /**
         * @event model.auth.beforeImpersonate
         * Called after the model is booted
         *
         * Example usage:
         *
         *     $model->bindEvent('model.auth.beforeImpersonate', function (\October\Rain\Database\Model|false $oldUser) use (\October\Rain\Database\Model $model) {
         *         \Log::info($oldUser->full_name . ' is now impersonating ' . $model->full_name);
         *     });
         *
         */
        $user->fireEvent('model.auth.beforeImpersonate', [$oldUser]);

        $this->login($user, false);

        if (!$this->isImpersonator()) {
            Session::put($this->sessionKey.'_impersonate', $oldSession);
        }
    }

    /**
     * Stop the current session being impersonated and
     * authenticate as the impersonator again
     */
    public function stopImpersonate()
    {
        $currentSession = Session::get($this->sessionKey);
        $currentUser = !empty($currentSession[0]) ? $this->findUserById($currentSession[0]) : false;
        $oldSession = Session::pull($this->sessionKey.'_impersonate');
        $oldUser = !empty($oldSession[0]) ? $this->findUserById($oldSession[0]) : false;

        if ($currentUser) {
            /**
             * @event model.auth.afterImpersonate
             * Called after the model is booted
             *
             * Example usage:
             *
             *     $model->bindEvent('model.auth.afterImpersonate', function (\October\Rain\Database\Model|false $oldUser) use (\October\Rain\Database\Model $model) {
             *         \Log::info($oldUser->full_name . ' has stopped impersonating ' . $model->full_name);
             *     });
             *
             */
            $currentUser->fireEvent('model.auth.afterImpersonate', [$oldUser]);
        }

        Session::put($this->sessionKey, $oldSession);
    }

    /**
     * Check to see if the current session is being impersonated
     *
     * @return bool
     */
    public function isImpersonator()
    {
        return !empty(Session::has($this->sessionKey.'_impersonate'));
    }

    /**
     * Get the original user doing the impersonation
     *
     * @return mixed Returns the User model for the impersonator if able, false if not
     */
    public function getImpersonator()
    {
        $impersonateArray = Session::get($this->sessionKey.'_impersonate');

        /*
         * Check supplied session/cookie is an array (user id, persist code)
         */
        if (!is_array($impersonateArray) || count($impersonateArray) !== 2) {
            return false;
        }

        $id = $impersonateArray[0];

        return $this->createUserModel()->find($id);
    }
}
