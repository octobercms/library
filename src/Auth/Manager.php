<?php namespace October\Rain\Auth;

use Request;
use Illuminate\Contracts\Auth\StatefulGuard;

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
    use \October\Rain\Auth\Concerns\HasStatefulGuard;
    use \October\Rain\Auth\Concerns\HasProviderProxy;
    use \October\Rain\Auth\Concerns\HasGuard;

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
     * @var bool|null checkCache adds a specific cache to the check() method to reduce
     * the number of database calls.
     */
    protected $checkCache = null;

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
     * validateInternal a user's credentials, method used internally.
     * @return Models\User
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
}
