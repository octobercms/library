<?php namespace October\Rain\Auth\Models;

use Str;
use Hash;
use October\Rain\Database\Model;
use InvalidArgumentException;
use Exception;

/**
 * User model
 */
class User extends Model implements \Illuminate\Contracts\Auth\Authenticatable
{
    use \October\Rain\Database\Traits\Hashable;
    use \October\Rain\Database\Traits\Purgeable;
    use \October\Rain\Database\Traits\Validation;

    /**
     * @var string table associated with the model
     */
    protected $table = 'users';

    /**
     * @var array rules for validation
     */
    public $rules = [
        'email' => 'required|between:3,255|email|unique:users',
        'password' => 'required:create|min:2|confirmed',
        'password_confirmation' => 'required_with:password|min:2'
    ];

    /**
     * @var array belongsToMany relation
     */
    public $belongsToMany = [
        'groups' => [Group::class, 'table' => 'users_groups']
    ];

    /**
     * @var array belongsTo relation
     */
    public $belongsTo = [
        'role' => Role::class
    ];

    /**
     * @var array dates attributes that should be mutated to dates
     */
    protected $dates = ['activated_at', 'last_login'];

    /**
     * @var array hidden attributes removed from the API representation of the model (ex. toArray())
     */
    protected $hidden = ['password', 'reset_password_code', 'activation_code', 'persist_code'];

    /**
     * @var array guarded attributes aren't mass assignable
     */
    protected $guarded = ['is_superuser', 'reset_password_code', 'activation_code', 'persist_code', 'role_id'];

    /**
     * @var array hashable list of attribute names which should be hashed using the Bcrypt hashing algorithm
     */
    protected $hashable = ['password', 'persist_code'];

    /**
     * @var array purgeable list of attribute names which should not be saved to the database
     */
    protected $purgeable = ['password_confirmation'];

    /**
     * @var array attributeNames array of custom attribute names
     */
    public $attributeNames = [];

    /**
     * @var array customMessages array of custom error messages
     */
    public $customMessages = [];

    /**
     * @var array jsonable attribute names that are json encoded and decoded from the database
     */
    protected $jsonable = ['permissions'];

    /**
     * allowedPermissionsValues
     *
     * Possible options:
     *   -1 => Deny (adds to array, but denies regardless of user's group).
     *    0 => Remove.
     *    1 => Add.
     *
     * @var array
     */
    protected $allowedPermissionsValues = [-1, 0, 1];

    /**
     * @var string loginAttribute
     */
    public static $loginAttribute = 'email';

    /**
     * @var string rememberTokenName is the column name of the "remember me" token
     */
    protected $rememberTokenName = 'persist_code';

    /**
     * @var array mergedPermissions for the user
     */
    protected $mergedPermissions;

    /**
     * @return string getLoginName returns the name for the user's login
     */
    public function getLoginName()
    {
        return static::$loginAttribute;
    }

    /**
     * @return mixed getLogin returns the user's login
     */
    public function getLogin()
    {
        return $this->{$this->getLoginName()};
    }

    /**
     * isSuperUser checks if the user is a super user - has access to everything
     * regardless of permissions
     * @return bool
     */
    public function isSuperUser()
    {
        return (bool) $this->is_superuser;
    }

    //
    // Events
    //

    /**
     * beforeLogin event
     */
    public function beforeLogin()
    {
    }

    /**
     * afterLogin event
     */
    public function afterLogin()
    {
        $this->last_login = $this->freshTimestamp();
        $this->forceSave();
    }

    /**
     * afterDelete deletes the user groups
     * @return bool
     */
    public function afterDelete()
    {
        if ($this->hasRelation('groups')) {
            $this->groups()->detach();
        }
    }

    //
    // Persistence (used by Cookies and Sessions)
    //

    /**
     * getPersistCode gets a code for when the user is persisted to a cookie or session
     * which identifies the user
     * @return string
     */
    public function getPersistCode()
    {
        $this->persist_code = $this->getRandomString();

        // Our code got hashed
        $persistCode = $this->persist_code;

        $this->forceSave();

        return $persistCode;
    }

    /**
     * checkPersistCode checks the given persist code
     * @param string $persistCode
     * @return bool
     */
    public function checkPersistCode($persistCode)
    {
        if (!$persistCode || !$this->persist_code) {
            return false;
        }

        return $persistCode === $this->persist_code;
    }

    //
    // Activation
    //

    /**
     * getIsActivatedAttribute is a get mutator for giving the activated property
     * @param mixed $activated
     * @return bool
     */
    public function getIsActivatedAttribute($activated)
    {
        return (bool) $activated;
    }

    /**
     * getActivationCode for the given user
     * @return string
     */
    public function getActivationCode()
    {
        $this->activation_code = $activationCode = $this->getRandomString();

        $this->forceSave();

        return $activationCode;
    }

    /**
     * attemptActivation the given user by checking the activate code. If the user
     * is activated already, an Exception is thrown
     * @param string $activationCode
     * @return bool
     */
    public function attemptActivation($activationCode)
    {
        if ($this->is_activated) {
            throw new Exception('User is already active!');
        }

        if ($activationCode === $this->activation_code) {
            $this->activation_code = null;
            $this->is_activated = true;
            $this->activated_at = $this->freshTimestamp();
            $this->forceSave();
            return true;
        }

        return false;
    }

    //
    // Password
    //

    /**
     * checkPassword checks the password passed matches the user's password
     * @param string $password
     * @return bool
     */
    public function checkPassword($password)
    {
        return Hash::check($password, $this->password);
    }

    /**
     * getResetPasswordCode gets a reset password code for the given user
     * @return string
     */
    public function getResetPasswordCode()
    {
        $this->reset_password_code = $resetCode = $this->getRandomString();
        $this->forceSave();
        return $resetCode;
    }

    /**
     * checkResetPasswordCode checks if the provided user reset password code is
     * valid without actually resetting the password
     * @param string $resetCode
     * @return bool
     */
    public function checkResetPasswordCode($resetCode)
    {
        if (!$resetCode || !$this->reset_password_code) {
            return false;
        }

        return $this->reset_password_code === $resetCode;
    }

    /**
     * attemptResetPassword a user's password by matching the reset code generated with the users
     * @param string $resetCode
     * @param string $newPassword
     * @return bool
     */
    public function attemptResetPassword($resetCode, $newPassword)
    {
        if ($this->checkResetPasswordCode($resetCode)) {
            $this->password = $newPassword;
            $this->reset_password_code = null;
            return $this->forceSave();
        }

        return false;
    }

    /**
     * clearResetPassword wipes out the data associated with resetting a password
     * @return void
     */
    public function clearResetPassword()
    {
        if ($this->reset_password_code) {
            $this->reset_password_code = null;
            $this->forceSave();
        }
    }

    /**
     * setPasswordAttribute protects the password from being reset to null
     */
    public function setPasswordAttribute($value)
    {
        if ($this->exists && empty($value)) {
            unset($this->attributes['password']);
        }
        else {
            $this->attributes['password'] = $value;

            // Password has changed, log out all users
            $this->attributes['persist_code'] = null;
        }
    }

    //
    // Permissions, Groups & Role
    //

    /**
     * getGroups returns an array of groups which the given user belongs to
     * @return array
     */
    public function getGroups()
    {
        return $this->groups;
    }

    /**
     * getRole returns the role assigned to this user
     * @return October\Rain\Auth\Models\Role
     */
    public function getRole()
    {
        return $this->role;
    }

    /**
     * addGroup adds the user to the given group
     * @param Group $group
     * @return bool
     */
    public function addGroup($group)
    {
        if (!$this->inGroup($group)) {
            $this->groups()->attach($group);
            $this->reloadRelations('groups');
        }

        return true;
    }

    /**
     * removeGroup removes the user from the given group
     * @param Group $group
     * @return bool
     */
    public function removeGroup($group)
    {
        if ($this->inGroup($group)) {
            $this->groups()->detach($group);
            $this->reloadRelations('groups');
        }

        return true;
    }

    /**
     * inGroup see if the user is in the given group
     * @param Group $group
     * @return bool
     */
    public function inGroup($group)
    {
        foreach ($this->getGroups() as $_group) {
            if ($_group->getKey() === $group->getKey()) {
                return true;
            }
        }

        return false;
    }

    /**
     * getMergedPermissions returns an array of merged permissions for each group the user is in
     * @return array
     */
    public function getMergedPermissions()
    {
        if (!$this->mergedPermissions) {
            $permissions = [];

            if (($role = $this->getRole()) && is_array($role->permissions)) {
                $permissions = array_merge($permissions, $role->permissions);
            }

            if (is_array($this->permissions)) {
                $permissions = array_merge($permissions, $this->permissions);
            }

            $this->mergedPermissions = $permissions;
        }

        return $this->mergedPermissions;
    }

    /**
     * hasAccess sees if a user has access to the passed permission(s). Permissions are merged
     * from all groups the user belongs to and then are checked against the passed permission(s).
     *
     * If multiple permissions are passed, the user must have access to all permissions passed
     * through, unless the "all" flag is set to false.
     *
     * Super users have access no matter what.
     *
     * @param  string|array  $permissions
     * @param  bool  $all
     * @return bool
     */
    public function hasAccess($permissions, $all = true)
    {
        if ($this->isSuperUser()) {
            return true;
        }

        return $this->hasPermission($permissions, $all);
    }

    /**
     * hasPermission sees if a user has access to the passed permission(s). Permissions are merged
     * from all groups the user belongs to and then are checked against the passed permission(s).
     *
     * If multiple permissions are passed, the user must have access to all permissions passed
     * through, unless the "all" flag is set to false.
     *
     * Super users don't have access no matter what.
     *
     * @param  string|array  $permissions
     * @param  bool  $all
     * @return bool
     */
    public function hasPermission($permissions, $all = true)
    {
        $mergedPermissions = $this->getMergedPermissions();

        if (!is_array($permissions)) {
            $permissions = [$permissions];
        }

        foreach ($permissions as $permission) {
            // We will set a flag now for whether this permission was
            // matched at all.
            $matched = true;

            // Now, let's check if the permission ends in a wildcard "*" symbol.
            // If it does, we'll check through all the merged permissions to see
            // if a permission exists which matches the wildcard.
            if ((strlen($permission) > 1) && ends_with($permission, '*')) {
                $matched = false;

                foreach ($mergedPermissions as $mergedPermission => $value) {
                    // Strip the '*' off the end of the permission.
                    $checkPermission = substr($permission, 0, -1);

                    // We will make sure that the merged permission does not
                    // exactly match our permission, but starts with it.
                    if (
                        $checkPermission !== $mergedPermission &&
                        starts_with($mergedPermission, $checkPermission) &&
                        (int) $value === 1
                    ) {
                        $matched = true;
                        break;
                    }
                }
            }
            elseif ((strlen($permission) > 1) && starts_with($permission, '*')) {
                $matched = false;

                foreach ($mergedPermissions as $mergedPermission => $value) {
                    // Strip the '*' off the beginning of the permission.
                    $checkPermission = substr($permission, 1);

                    // We will make sure that the merged permission does not
                    // exactly match our permission, but ends with it.
                    if (
                        $checkPermission !== $mergedPermission &&
                        ends_with($mergedPermission, $checkPermission) &&
                        (int) $value === 1
                    ) {
                        $matched = true;
                        break;
                    }
                }
            }
            else {
                $matched = false;

                foreach ($mergedPermissions as $mergedPermission => $value) {
                    // This time check if the mergedPermission ends in wildcard "*" symbol.
                    if ((strlen($mergedPermission) > 1) && ends_with($mergedPermission, '*')) {
                        $matched = false;

                        // Strip the '*' off the end of the permission.
                        $checkMergedPermission = substr($mergedPermission, 0, -1);

                        // We will make sure that the merged permission does not
                        // exactly match our permission, but starts with it.
                        if (
                            $checkMergedPermission !== $permission &&
                            starts_with($permission, $checkMergedPermission) &&
                            (int) $value === 1
                        ) {
                            $matched = true;
                            break;
                        }
                    }
                    // Otherwise, we'll fallback to standard permissions checking where
                    // we match that permissions explicitly exist.
                    elseif (
                        $permission === $mergedPermission &&
                        (int) $mergedPermissions[$permission] === 1
                    ) {
                        $matched = true;
                        break;
                    }
                }
            }

            // Now, we will check if we have to match all permissions or any permission and return
            // accordingly.
            if ($all === true && $matched === false) {
                return false;
            }
            elseif ($all === false && $matched === true) {
                return true;
            }
        }

        return !($all === false);
    }

    /**
     * hasAnyAccess returns if the user has access to any of the given permissions
     * @param  array  $permissions
     * @return bool
     */
    public function hasAnyAccess(array $permissions)
    {
        return $this->hasAccess($permissions, false);
    }

    /**
     * setPermissionsAttribute validates any set permissions
     * @param array $permissions
     * @return void
     */
    public function setPermissionsAttribute($permissions)
    {
        $permissions = json_decode($permissions, true) ?: [];
        foreach ($permissions as $permission => &$value) {
            if (!in_array($value = (int) $value, $this->allowedPermissionsValues)) {
                throw new InvalidArgumentException(sprintf(
                    'Invalid value "%s" for permission "%s" given.',
                    $value,
                    $permission
                ));
            }

            if ($value === 0) {
                unset($permissions[$permission]);
            }
        }

        $this->attributes['permissions'] = !empty($permissions) ? json_encode($permissions) : '';
    }

    //
    // User Interface
    //

    /**
     * getAuthIdentifierName gets the name of the unique identifier for the user
     * @return string
     */
    public function getAuthIdentifierName()
    {
        return $this->getKeyName();
    }

    /**
     * getAuthIdentifier gets the unique identifier for the user
     * @return mixed
     */
    public function getAuthIdentifier()
    {
        return $this->{$this->getAuthIdentifierName()};
    }

    /**
     * getAuthPassword gets the password for the user
     * @return string
     */
    public function getAuthPassword()
    {
        return $this->password;
    }

    /**
     * getReminderEmail gets the e-mail address where password reminders are sent
     * @return string
     */
    public function getReminderEmail()
    {
        return $this->email;
    }

    /**
     * getRememberToken gets the token value for the "remember me" session
     * @return string
     */
    public function getRememberToken()
    {
        return $this->getPersistCode();
    }

    /**
     * setRememberToken sets the token value for the "remember me" session
     * @param  string $value
     * @return void
     */
    public function setRememberToken($value)
    {
        $this->persist_code = $value;
    }

    /**
     * getRememberTokenName gets the column name for the "remember me" token
     * @return string
     */
    public function getRememberTokenName()
    {
        return $this->rememberTokenName;
    }

    //
    // Helpers
    //

    /**
     * getRandomString generates a random string
     * @return string
     */
    public function getRandomString($length = 42)
    {
        return Str::random($length);
    }
}
