<?php namespace October\Rain\Auth;

use Cookie;
use Session;
use Request;
use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Manager for authentication
 *
 * @package october\auth
 * @author Alexey Bobkov, Samuel Georges
 */
class Manager implements \Illuminate\Contracts\Auth\StatefulGuard
{
    use \October\Rain\Support\Traits\Singleton;

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

    //
    // User
    //

    /**
     * createUserModel instance
     */
    public function createUserModel()
    {
        $class = '\\'.ltrim($this->userModel, '\\');
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
     * setUser will set the current user.
     */
    public function setUser(Authenticatable $user)
    {
        $this->user = $user;
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
                if ($credential === 'password') {
                    throw new AuthException(sprintf(
                        'A user was found to match all plain text credentials however hashed credential "%s" did not match.',
                        $credential
                    ));
                }

                // User not found
                throw new AuthException('A user was not found with the given credentials.');
            }
        }

        return $user;
    }

    /**
     * validateUserModel perform additional checks on the user model.
     * @param $user
     * @return boolean
     */
    protected function validateUserModel($user)
    {
        return $user instanceof $this->userModel;
    }

    //
    // Role
    //

    /**
     * createRoleModel creates an instance of the role model.
     * @return Models\Role
     */
    public function createRoleModel()
    {
        $class = '\\'.ltrim($this->roleModel, '\\');
        return new $class();
    }

    //
    // Throttle
    //

    /**
     * createThrottleModel creates an instance of the throttle model.
     * @return Models\Throttle
     */
    public function createThrottleModel()
    {
        $class = '\\'.ltrim($this->throttleModel, '\\');
        return new $class();
    }

    /**
     * findThrottleByLogin and ip address
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
     * findThrottleByUserId and ip address
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
            $query->where(function ($query) use ($ipAddress) {
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

    /**
     * clearThrottleForUserId unsuspends and clears all throttles records for a user
     */
    public function clearThrottleForUserId($userId): void
    {
        if (!$userId) {
            return;
        }

        $model = $this->createThrottleModel();

        $throttles = $model->where('user_id', $userId)->get();

        foreach ($throttles as $throttle) {
            $throttle->unsuspend();
        }
    }

    //
    // Business Logic
    //

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
                'Cannot login user "%s" as they are not activated.',
                $login
            ));
        }

        $this->user = $user;

        /*
         * Create session/cookie data to persist the session
         */
        if ($this->useSession) {
            $this->setPersistCodeToSession($user, $remember);
        }

        /*
         * Fire the 'afterLogin' event
         */
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

    //
    // Session
    //

    /**
     * setPersistCodeToSession stores the user persistence in the session and cookie.
     */
    protected function setPersistCodeToSession($user, bool $remember = true, bool $impersonating = false): void
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

    //
    // Impersonation
    //

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
    public function impersonateRole($role): void
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
    public function stopImpersonateRole(): void
    {
        Session::forget($this->sessionKey.'_impersonate_role');
    }

    /**
     * applyRoleImpersonation tells the user model to impersonate the role
     */
    protected function applyRoleImpersonation($user): void
    {
        $roleId = Session::get($this->sessionKey.'_impersonate_role');

        if ($role = $this->createRoleModel()->find($roleId)) {
            $user->setRoleImpersonation($role);
        }
    }
}
