<?php namespace October\Rain\Auth;

use Cookie;
use Session;
use Request;
use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Manager for authentication
 *
 * @package october\auth
 * @author Alexey Bobkov, Samuel Georges
 */
class Manager implements StatefulGuard
{
    use \October\Rain\Support\Traits\Singleton;
    use \October\Rain\Auth\Concerns\HasUser;
    use \October\Rain\Auth\Concerns\HasSession;
    use \October\Rain\Auth\Concerns\HasThrottle;
    use \October\Rain\Auth\Concerns\HasImpersonation;

    /**
     * @var Models\User user that is currently logged in
     */
    protected $user;

    /**
     * @var array throttle cache in memory
     * [md5($userId.$ipAddress) => $this->throttleModel]
     */
    protected $throttle = [];

    /**
     * @var string userModel class
     */
    protected $userModel = Models\User::class;

    /**
     * @var string roleModel class
     */
    protected $roleModel = Models\Role::class;

    /**
     * @var string groupModel class
     */
    protected $groupModel = Models\Group::class;

    /**
     * @var string throttleModel class
     */
    protected $throttleModel = Models\Throttle::class;

    /**
     * @var bool useThrottle flag to enable login throttling.
     */
    protected $useThrottle = true;

    /**
     * @var bool useRehash flag to enable password rehashing.
     */
    protected $useRehash = true;

    /**
     * @var bool useSession internal flag to toggle using the session for
     * the current authentication request.
     */
    protected $useSession = true;

    /**
     * @var bool requireActivation rlag to require users to be activated to login.
     */
    protected $requireActivation = true;

    /**
     * @var string sessionKey to store the auth session data in.
     */
    protected $sessionKey = 'october_auth';

    /**
     * @var bool viaRemember indicates if the user was authenticated via a recaller cookie.
     */
    protected $viaRemember = false;

    /**
     * @var string ipAddress of this request.
     */
    public $ipAddress = '0.0.0.0';

    /**
     * init the singleton
     */
    protected function init()
    {
        $this->ipAddress = Request::ip();
    }

    /**
     * register a user with the provided credentials with optional flags for
     * activating the newly created user and automatically logging them in.
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
     * validate a user's credentials.
     * @return bool
     */
    public function validate(array $credentials = [])
    {
        return !!$this->validateInternal($credentials);
    }

    /**
     * validateInternal a user's credentials, method used internally.
     * @return User
     */
    protected function validateInternal(array $credentials = [])
    {
        // Default to the login name field or fallback to a hard-coded 'login' value
        $loginName = $this->createUserModel()->getLoginName();
        $loginCredentialKey = isset($credentials[$loginName]) ? $loginName : 'login';

        if (empty($credentials[$loginCredentialKey])) {
            throw new AuthException("The {$loginCredentialKey} attribute is required.", 100);
        }

        if (empty($credentials['password'])) {
            throw new AuthException('The password attribute is required.', 102);
        }

        // If the fallback 'login' was provided and did not match the necessary
        // login name, swap it over
        if ($loginCredentialKey !== $loginName) {
            $credentials[$loginName] = $credentials[$loginCredentialKey];
            unset($credentials[$loginCredentialKey]);
        }

        // If throttling is enabled, check they are not locked out first and foremost.
        if ($this->useThrottle) {
            $throttle = $this->findThrottleByLogin($credentials[$loginName], $this->ipAddress);
            $throttle->check();
        }

        // Look up the user by authentication credentials.
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

        // Rehash password if needed
        if ($this->useRehash) {
            $user->attemptRehashPassword($credentials['password']);
        }

        return $user;
    }

    /**
     * authenticate the given user according to the passed credentials
     */
    public function authenticate(array $credentials, $remember = true)
    {
        $user = $this->validateInternal($credentials);

        $user->clearResetPassword();

        $this->login($user, $remember);

        return $this->user;
    }

    /**
     * check to see if the user is logged in and activated, and hasn't been banned or suspended.
     * @return bool
     */
    public function check()
    {
        if (is_null($this->user)) {
            // Find persistence code
            $userArray = $this->getPersistCodeFromSession();
            if (!$userArray) {
                return false;
            }

            [$id, $persistCode] = $userArray;

            // Look up user
            if (!$user = $this->findUserById($id)) {
                return false;
            }

            // Confirm the persistence code is valid, otherwise reject
            if (!$user->checkPersistCode($persistCode)) {
                return false;
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
     * login the given user and sets properties in the session.
     * @throws AuthException If the user is not activated and $this->requireActivation = true
     */
    public function login(Authenticatable $user, $remember = true)
    {
        // Fire the 'beforeLogin' event
        $user->beforeLogin();

        // Activation is required, user not activated
        if ($this->requireActivation && !$user->is_activated) {
            $login = $user->getLogin();
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
