<?php namespace October\Rain\Auth\Models;

use Hash;
use October\Rain\Database\Model;
use October\Rain\Auth\Hash\HasherBase;
use InvalidArgumentException;
use RuntimeException;
use Exception;
use DateTime;

/**
 * User model
 */
class User extends Model
{
    use \October\Rain\Database\Traits\Hashable;
    use \October\Rain\Database\Traits\Purgeable;
    use \October\Rain\Database\Traits\Validation;

    /**
     * @var string The table associated with the model.
     */
    protected $table = 'users';

    /**
     * @var array Validation rules
     */
    public $rules = [
        'email' => 'required|between:3,64|email|unique:users',
        'password' => 'required:create|between:2,32|confirmed',
        'password_confirmation' => 'required_with:password|between:2,32'
    ];

    /**
     * @var array Relations
     */
    public $belongsToMany = [
        'groups' => ['October\Rain\Auth\Models\Group', 'table' => 'users_groups']
    ];

    /**
     * @var array The attributes that should be hidden for arrays.
     */
    protected $hidden = ['password', 'reset_password_code', 'activation_code', 'persist_code'];

    /**
     * @var array The attributes that aren't mass assignable.
     */
    protected $guarded = ['reset_password_code', 'activation_code', 'persist_code'];

    /**
     * @var array List of attribute names which should be hashed using the Bcrypt hashing algorithm.
     */
    protected $hashable = ['password', 'persist_code'];

    /**
     * @var array List of attribute names which should not be saved to the database.
     */
    protected $purgeable = ['password_confirmation'];

    /**
     * @var array The array of custom attribute names.
     */
    public $attributeNames = [];

    /**
     * @var array The array of custom error messages.
     */
    public $customMessages = [];

    /**
     * @var array List of attribute names which are json encoded and decoded from the database.
     */
    protected $jsonable = ['permissions'];

    /**
     * Allowed permissions values.
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
     * @var string The login attribute.
     */
    public static $loginAttribute = 'email';

    /**
     * @var array The user groups.
     */
    protected $userGroups;

    /**
     * @var array The user merged permissions.
     */
    protected $mergedPermissions;

    /**
     * @return string Returns the name for the user's login.
     */
    public function getLoginName()
    {
        return static::$loginAttribute;
    }

    /**
     * @return mixed Returns the user's login.
     */
    public function getLogin()
    {
        return $this->{$this->getLoginName()};
    }

    /**
     * Checks if the user is a super user - has access to everything regardless of permissions.
     * @return bool
     */
    public function isSuperUser()
    {
        return $this->hasPermission('superuser');
    }

    //
    // Events
    //

    public function afterLogin()
    {
        $this->last_login = $this->freshTimestamp();
        $this->forceSave();
    }

    /**
     * Delete the user groups
     * @return bool
     */
    public function afterDelete()
    {
        if ($this->hasRelation('groups'))
            $this->groups()->detach();
    }

    //
    // Persistence (used by Cookies and Sessions)
    //

    /**
     * Gets a code for when the user is persisted to a cookie or session which identifies the user.
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
     * Checks the given persist code.
     * @param string $persistCode
     * @return bool
     */
    public function checkPersistCode($persistCode)
    {
        if (!$persistCode)
            return false;

        return $persistCode == $this->persist_code;
    }

    //
    // Activation
    //

    /**
     * Get mutator for giving the activated property.
     * @param mixed $activated
     * @return bool
     */
    public function getIsActivatedAttribute($activated)
    {
        return (bool) $activated;
    }

    /**
     * Get an activation code for the given user.
     * @return string
     */
    public function getActivationCode()
    {
        $this->activation_code = $activationCode = $this->getRandomString();

        $this->forceSave();

        return $activationCode;
    }

    /**
     * Attempts to activate the given user by checking the activate code. If the user is activated already, an Exception is thrown.
     * @param string $activationCode
     * @return bool
     */
    public function attemptActivation($activationCode)
    {
        if ($this->is_activated)
            throw new Exception('User is already active!');

        if ($activationCode == $this->activation_code) {
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
     * Checks the password passed matches the user's password.
     * @param string $password
     * @return bool
     */
    public function checkPassword($password)
    {
        return Hash::check($password, $this->password);
    }

    /**
     * Get a reset password code for the given user.
     * @return string
     */
    public function getResetPasswordCode()
    {
        $this->reset_password_code = $resetCode = $this->getRandomString();
        $this->forceSave();
        return $resetCode;
    }

    /**
     * Checks if the provided user reset password code is valid without actually resetting the password.
     * @param string $resetCode
     * @return bool
     */
    public function checkResetPasswordCode($resetCode)
    {
        return ($this->reset_password_code == $resetCode);
    }

    /**
     * Attempts to reset a user's password by matching the reset code generated with the user's.
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
     * Wipes out the data associated with resetting a password.
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
     * Protects the password from being reset to null.
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
    // Permissions & Groups
    //

    /**
     * Returns an array of groups which the given user belongs to.
     * @return array
     */
    public function getGroups()
    {
        if (!$this->userGroups)
            $this->userGroups = $this->groups()->get();

        return $this->userGroups;
    }

    /**
     * Adds the user to the given group.
     * @param Group $group
     * @return bool
     */
    public function addGroup($group)
    {
        if (!$this->inGroup($group)) {
            $this->groups()->attach($group);
            $this->userGroups = null;
        }

        return true;
    }

    /**
     * Removes the user from the given group.
     * @param Group $group
     * @return bool
     */
    public function removeGroup($group)
    {
        if ($this->inGroup($group)) {
            $this->groups()->detach($group);
            $this->userGroups = null;
        }

        return true;
    }

    /**
     * See if the user is in the given group.
     * @param Group $group
     * @return bool
     */
    public function inGroup($group)
    {
        foreach ($this->getGroups() as $_group) {
            if ($_group->getKey() == $group->getKey())
                return true;
        }

        return false;
    }

    /**
     * Returns an array of merged permissions for each group the user is in.
     * @return array
     */
    public function getMergedPermissions()
    {
        if (!$this->mergedPermissions) {
            $permissions = [];

            foreach ($this->getGroups() as $group) {
                if (!is_array($group->permissions))
                    continue;

                $permissions = array_merge($permissions, $group->permissions);
            }

            if (is_array($this->permissions))
                $permissions = array_merge($permissions, $this->permissions);

            $this->mergedPermissions = $permissions;
        }

        return $this->mergedPermissions;
    }

    /**
     * See if a user has access to the passed permission(s).
     * Permissions are merged from all groups the user belongs to
     * and then are checked against the passed permission(s).
     *
     * If multiple permissions are passed, the user must
     * have access to all permissions passed through, unless the
     * "all" flag is set to false.
     *
     * Super users have access no matter what.
     *
     * @param  string|array  $permissions
     * @param  bool  $all
     * @return bool
     */
    public function hasAccess($permissions, $all = true)
    {
        if ($this->isSuperUser())
            return true;

        return $this->hasPermission($permissions, $all);
    }

    /**
     * See if a user has access to the passed permission(s).
     * Permissions are merged from all groups the user belongs to
     * and then are checked against the passed permission(s).
     *
     * If multiple permissions are passed, the user must
     * have access to all permissions passed through, unless the
     * "all" flag is set to false.
     *
     * Super users DON'T have access no matter what.
     *
     * @param  string|array  $permissions
     * @param  bool  $all
     * @return bool
     */
    public function hasPermission($permissions, $all = true)
    {
        $mergedPermissions = $this->getMergedPermissions();

        if (!is_array($permissions))
            $permissions = [$permissions];

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
                    if ($checkPermission != $mergedPermission && starts_with($mergedPermission, $checkPermission) && $value == 1) {
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
                    if ($checkPermission != $mergedPermission && ends_with($mergedPermission, $checkPermission) && $value == 1) {
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
                        if ($checkMergedPermission != $permission && starts_with($permission, $checkMergedPermission) && $value == 1) {
                            $matched = true;
                            break;
                        }
                    }

                    // Otherwise, we'll fallback to standard permissions checking where
                    // we match that permissions explicitly exist.
                    elseif ($permission == $mergedPermission && $mergedPermissions[$permission] == 1) {
                        $matched = true;
                        break;
                    }
                }
            }

            // Now, we will check if we have to match all
            // permissions or any permission and return
            // accordingly.
            if ($all === true && $matched === false) {
                return false;
            }
            elseif ($all === false && $matched === true) {
                return true;
            }
        }

        if ($all === false)
            return false;

        return true;
    }

    /**
     * Returns if the user has access to any of the given permissions.
     * @param  array  $permissions
     * @return bool
     */
    public function hasAnyAccess(array $permissions)
    {
        return $this->hasAccess($permissions, false);
    }

    /**
     * Validate any set permissions.
     * @param array $permissions
     * @return void
     */
    public function setPermissionsAttribute($permissions)
    {
        $permissions = json_decode($permissions, true);
        foreach ($permissions as $permission => &$value) {
            if (!in_array($value = (int)$value, $this->allowedPermissionsValues))
                throw new InvalidArgumentException(sprintf('Invalid value "%s" for permission "%s" given.', $value, $permission));

            if ($value === 0)
                unset($permissions[$permission]);
        }

        $this->attributes['permissions'] = (!empty($permissions)) ? json_encode($permissions) : '';
    }

    //
    // User Interface
    //

    /**
     * Get the unique identifier for the user.
     * @return mixed
     */
    public function getAuthIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Get the password for the user.
     * @return string
     */
    public function getAuthPassword()
    {
        return $this->password;
    }

    /**
     * Get the e-mail address where password reminders are sent.
     * @return string
     */
    public function getReminderEmail()
    {
        return $this->email;
    }

    /**
     * Get the token value for the "remember me" session.
     * @return string
     */
    public function getRememberToken()
    {
        return $this->getPersistCode();
    }

    /**
     * Set the token value for the "remember me" session.
     * @param  string $value
     * @return void
     */
    public function setRememberToken($value)
    {
        $this->persist_code = $value;
    }

    /**
     * Get the column name for the "remember me" token.
     * @return string
     */
    public function getRememberTokenName()
    {
        return 'persist_code';
    }

    //
    // Helpers
    //

    /**
     * Generate a random string
     * @return string
     */
    public function getRandomString($length = 42)
    {
        /*
         * Use OpenSSL (if available)
         */
        if (function_exists('openssl_random_pseudo_bytes')) {
            $bytes = openssl_random_pseudo_bytes($length * 2);

            if ($bytes === false)
                throw new RuntimeException('Unable to generate a random string');

            return substr(str_replace(['/', '+', '='], '', base64_encode($bytes)), 0, $length);
        }

        $pool = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';

        return substr(str_shuffle(str_repeat($pool, 5)), 0, $length);
    }

    /**
     * Get the attributes that should be converted to dates.
     * @return array
     */
    public function getDates()
    {
        return array_merge(parent::getDates(), ['activated_at', 'last_login']);
    }
}