<?php namespace October\Rain\Auth;

use Hash;
use Cookie;
use Session;
use Request;

class Manager
{
    use \October\Rain\Support\Traits\Singleton;

    protected $user;

    protected $userModel = 'October\Rain\Auth\Models\User';
    
    protected $groupModel = 'October\Rain\Auth\Models\Group';
    
    protected $throttleModel = 'October\Rain\Auth\Models\Throttle';
    
    protected $useThrottle = true;

    protected $ipAddress = '0.0.0.0';

    protected $sessionKey = 'october_auth';

    protected function init()
    {
        $this->setIpAddress(Request::getClientIp());
    }

    /**
     * Sets the IP address
     * @param string $ipAddress
     * @return void
     */
    public function setIpAddress($ipAddress)
    {
        $this->ipAddress = $ipAddress;
    }

    /**
     * Gets the IP address
     * @return string
     */
    public function getIpAddress()
    {
        return $this->ipAddress;
    }

    //
    // User
    //

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
        if (is_null($this->user))
            $this->check();

        return $this->user;
    }

    /**
     * Finds a user by the login value.
     * @param string $id
     */
    public function findUserById($id)
    {
        $model = $this->createUserModel();
        $user = $model->newQuery()->find($id);
        return $user ?: null;
    }

    /**
     * Finds a user by the login value.
     * @param string $login
     */
    public function findUserByLogin($login)
    {
        $model = $this->createUserModel();
        $user = $model->newQuery()->where($model->getLoginName(), $login)->first();
        return $user ?: null;
    }

    /**
     * Finds a user by the given credentials.
     */
    public function findUserByCredentials(array $credentials)
    {
        $model = $this->createUserModel();
        $loginName = $model->getLoginName();

        if (!array_key_exists($loginName, $credentials))
            throw new \InvalidArgumentException("Login attribute [$loginName] was not provided.");

        $query = $model->newQuery();
        $hashableAttributes = $model->getHashableAttributes();
        $hashedCredentials = array();

        // Build query from given credentials
        foreach ($credentials as $credential => $value) {
            // Remove hashed attributes to check later as we need to check these
            // values after we retrieved them because of salts
            if (in_array($credential, $hashableAttributes))
                $hashedCredentials = array_merge($hashedCredentials, array($credential => $value));
            else
                $query = $query->where($credential, '=', $value);
        }

        if (!$user = $query->first())
            throw new \Exception("A user was not found with the given credentials.");

        // Now check the hashed credentials match ours
        foreach ($hashedCredentials as $credential => $value) {

            if (!Hash::check($value, $user->{$credential})) {
                $message = "A user was found to match all plain text credentials however hashed credential [$credential] did not match.";

                // Wrong password
                if ($credential == 'password')
                    throw new \Exception($message);

                // User not found
                throw new \Exception($message);
            }
        }

        return $user;
    }

    /**
     * Registers a user by giving the required credentials
     * and an optional flag for whether to activate the user.
     * @param array $credentials
     * @param bool $activate
     * @return User
     */
    public function register(array $credentials, $activate = false)
    {
        $user = $this->createUserModel();
        $user->fill($credentials);
        $user->save();

        if ($activate)
            $user->attemptActivation($user->getActivationCode());

        return $this->user = $user;
    }

    /*
     * Creates a new instance of the user model
     */
    public function createUserModel()
    {
        $class = '\\'.ltrim($this->userModel, '\\');
        $user = new $class();
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
     * Find a throttle record by a user's login and visitor's ip address
     */
    public function findThrottleByLogin($loginName, $ipAddress)
    {
        $user = $this->findUserByLogin($loginName);
        if (!$user)
            throw new \Exception("A user could not be found with a login value of [$loginName].");

        $userId = $user->getId();
        return $this->findThrottleByUserId($userId, $ipAddress);
    }

    /**
     * Find a throttle record by a user's id and visitor's ip address
     */
    public function findThrottleByUserId($userId, $ipAddress = null)
    {
        $model = $this->createThrottleModel();
        $query = $model->where('user_id', '=', $userId);

        if ($ipAddress) {
            $query->where(function($query) use ($ipAddress) {
                $query->where('ip_address', '=', $ipAddress);
                $query->orWhere('ip_address', '=', NULL);
            });
        }

        if (!$throttle = $query->first()) {
            $throttle = $this->createThrottleModel();
            $throttle->user_id = $userId;
            if ($ipAddress)
                $throttle->ip_address = $ipAddress;

            $throttle->save();
        }

        return $throttle;
    }

    //
    // Business Logic
    //

    /**
     * Attempts to authenticate the given user
     * according to the passed credentials.
     */
    public function authenticate(array $credentials, $remember = true)
    {
        // We'll default to the login name field, but fallback to a hard-coded
        // 'login' key in the array that was passed.
        $loginName = $this->createUserModel()->getLoginName();
        $loginCredentialKey = (isset($credentials[$loginName])) ? $loginName : 'login';

        if (empty($credentials[$loginCredentialKey]))
            throw new \Exception("The [$loginCredentialKey] attribute is required.");

        if (empty($credentials['password']))
            throw new \Exception('The password attribute is required.');

        // If the user did the fallback 'login' key for the login code which
        // did not match the actual login name, we'll adjust the array so the
        // actual login name is provided.
        if ($loginCredentialKey !== $loginName) {
            $credentials[$loginName] = $credentials[$loginCredentialKey];
            unset($credentials[$loginCredentialKey]);
        }

        // If throttling is enabled, we'll firstly check the throttle.
        // This will tell us if the user is banned before we even attempt
        // to authenticate them
        if ($this->useThrottle) {
            $throttle = $this->findThrottleByLogin($credentials[$loginName], $this->ipAddress);
            $throttle->check();
        }

        try {
            $user = $this->findUserByCredentials($credentials);
        }
        catch (\Exception $e)
        {
            if ($this->useThrottle)
                $throttle->addLoginAttempt();

            throw $e;
        }

        if ($this->useThrottle)
            $throttle->clearLoginAttempts();

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

            // Check session first, follow by cookie
            if (!($userArray = Session::get($this->sessionKey)) && !($userArray = Cookie::get($this->sessionKey)))
                return false;

            // Now check our user is an array with two elements,
            // the username followed by the persist code
            if (!is_array($userArray) || count($userArray) !== 2)
                return false;

            list($id, $persistCode) = $userArray;

            // Let's find our user
            $user = $this->createUserModel()->find($id);
            if (!$user)
                return false;

            // Great! Let's check the session's persist code
            // against the user. If it fails, somebody has tampered
            // with the cookie / session data and we're not allowing
            // a login
            if (!$user->checkPersistCode($persistCode))
                return false;

            // Now we'll set the user property
            $this->user = $user;
        }

        // Let's check our cached user is indeed activated
        if (!($user = $this->getUser()) || !$user->isActivated())
            return false;

        // If throttling is enabled we check it's status
        if ($this->useThrottle) {

            // Check the throttle status
            $throttle = $this->findThrottleByUserId($user->getId());

            if ($throttle->isBanned() || $throttle->isSuspended()) {
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
        if (!$user->isActivated()) {
            $login = $user->getLogin();
            throw new \Exception("Cannot login user [$login] as they are not activated.");
        }

        $this->user = $user;

        // Create an array of data to persist to the session and / or cookie
        $toPersist = array($user->getId(), $user->getPersistCode());

        // Set sessions
        Session::put($this->sessionKey, $toPersist);

        if ($remember)
            Cookie::queue(Cookie::forever($this->sessionKey, $toPersist));

        // The user model can attach any handlers
        // to the "recordLogin" event.
        $user->recordLogin();
    }

    /**
     * Logs the current user out.
     *
     * @return void
     */
    public function logout()
    {
        $this->user = null;

        Session::forget($this->sessionKey);
        Cookie::queue(Cookie::forget($this->sessionKey));
    }

}
