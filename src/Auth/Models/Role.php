<?php namespace October\Rain\Auth\Models;

use InvalidArgumentException;
use October\Rain\Database\Model;

/**
 * Role model
 */
class Role extends Model
{
    use \October\Rain\Database\Traits\Validation;

    /**
     * @var string table associated with the model
     */
    protected $table = 'roles';

    /**
     * @var array rules for validation
     */
    public $rules = [
        'name' => 'required|between:4,16|unique:role',
    ];

    /**
     * @var array hasMany relationship
     */
    public $hasMany = [
        'users' => User::class
    ];

    /**
     * @var array jsonable attribute names that are json encoded and decoded from the database
     */
    protected $jsonable = ['permissions'];

    /**
     * @var array allowedPermissionsValues
     *
     * Possible options:
     *    0 => Remove.
     *    1 => Add.
     */
    protected $allowedPermissionsValues = [0, 1];

    /**
     * @var array guarded attributes aren't mass assignable
     */
    protected $guarded = [];

    /**
     * hasAccess will see if a role has access to the passed permission(s)
     *
     * If multiple permissions are passed, the role must
     * have access to all permissions passed through, unless the
     * "all" flag is set to false.
     *
     * @param  string|array  $permissions
     * @param  bool  $all
     * @return bool
     */
    public function hasAccess($permissions, $all = true)
    {
        $rolePermissions = $this->permissions;

        if (!is_array($permissions)) {
            $permissions = (array) $permissions;
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

                foreach ($rolePermissions as $rolePermission => $value) {
                    // Strip the '*' off the end of the permission.
                    $checkPermission = substr($permission, 0, -1);

                    // We will make sure that the merged permission does not
                    // exactly match our permission, but starts with it.
                    if (
                        $checkPermission !== $rolePermission &&
                        starts_with($rolePermission, $checkPermission) &&
                        (int) $value === 1
                    ) {
                        $matched = true;
                        break;
                    }
                }
            }
            // Now, let's check if the permission starts in a wildcard "*" symbol.
            // If it does, we'll check through all the merged permissions to see
            // if a permission exists which matches the wildcard.
            elseif ((strlen($permission) > 1) && starts_with($permission, '*')) {
                $matched = false;

                foreach ($rolePermissions as $rolePermission => $value) {
                    // Strip the '*' off the start of the permission.
                    $checkPermission = substr($permission, 1);

                    // We will make sure that the merged permission does not
                    // exactly match our permission, but ends with it.
                    if (
                        $checkPermission !== $rolePermission &&
                        ends_with($rolePermission, $checkPermission) &&
                        (int) $value === 1
                    ) {
                        $matched = true;
                        break;
                    }
                }
            }
            else {
                $matched = false;

                foreach ($rolePermissions as $rolePermission => $value) {
                    // This time check if the rolePermission ends in wildcard "*" symbol.
                    if ((strlen($rolePermission) > 1) && ends_with($rolePermission, '*')) {
                        $matched = false;

                        // Strip the '*' off the end of the permission.
                        $checkGroupPermission = substr($rolePermission, 0, -1);

                        // We will make sure that the merged permission does not
                        // exactly match our permission, but starts with it.
                        if (
                            $checkGroupPermission !== $permission &&
                            starts_with($permission, $checkGroupPermission) &&
                            (int) $value === 1
                        ) {
                            $matched = true;
                            break;
                        }
                    }
                    // Otherwise, we'll fallback to standard permissions checking where
                    // we match that permissions explicitly exist.
                    elseif (
                        $permission === $rolePermission &&
                        (int) $rolePermissions[$permission] === 1
                    ) {
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

        return !($all === false);
    }

    /**
     * hasAnyAccess returns if the user has access to any of the given permissions
     * @param array $permissions
     * @return bool
     */
    public function hasAnyAccess(array $permissions)
    {
        return $this->hasAccess($permissions, false);
    }

    /**
     * setPermissionsAttribute validates the permissions when set
     * @param  array  $permissions
     * @return void
     */
    public function setPermissionsAttribute($permissions)
    {
        $permissions = json_decode($permissions, true);
        foreach ($permissions as $permission => $value) {
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
}
