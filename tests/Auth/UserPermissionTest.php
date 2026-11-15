<?php

use October\Rain\Auth\Models\Role;
use October\Rain\Auth\Models\User;

/**
 * UserPermissionTest covers how role and user permissions combine in hasPermission.
 */
class UserPermissionTest extends TestCase
{
    /**
     * testExplicitDenyOverridesRoleWildcard ensures a user deny wins over a wildcard grant from their role.
     */
    public function testExplicitDenyOverridesRoleWildcard()
    {
        $user = $this->makeUser(['admins.*' => 1], ['admins.manage' => -1]);

        $this->assertFalse($user->hasPermission('admins.manage'));
        $this->assertTrue($user->hasPermission('admins.roles'));
    }

    /**
     * testExplicitDenyOverridesRoleGrant ensures a user deny wins over the same permission granted by the role.
     */
    public function testExplicitDenyOverridesRoleGrant()
    {
        $user = $this->makeUser(['admins.manage' => 1], ['admins.manage' => -1]);

        $this->assertFalse($user->hasPermission('admins.manage'));
    }

    /**
     * testEmptyPermissionsAreMergedOnce ensures a user without permissions does not merge them again on every check.
     */
    public function testEmptyPermissionsAreMergedOnce()
    {
        $user = new class extends User {
            public $roleLookups = 0;

            public function getRole()
            {
                $this->roleLookups++;

                return null;
            }
        };

        $user->getMergedPermissions();
        $user->getMergedPermissions();

        $this->assertSame(1, $user->roleLookups);
    }

    /**
     * testRoleWildcardGrantsSubPermissions ensures a wildcard grant still covers permissions without a deny.
     */
    public function testRoleWildcardGrantsSubPermissions()
    {
        $user = $this->makeUser(['admins.*' => 1], []);

        $this->assertTrue($user->hasPermission('admins.manage'));
        $this->assertFalse($user->hasPermission('system.manage'));
    }

    /**
     * testUserGrantAddsToRole ensures a user grant adds a permission the role does not hold.
     */
    public function testUserGrantAddsToRole()
    {
        $user = $this->makeUser(['admins.roles' => 1], ['admins.manage' => 1]);

        $this->assertTrue($user->hasPermission(['admins.roles', 'admins.manage']));
    }

    /**
     * testChangedUserPermissionsAreMergedAgain ensures a revoked user permission stops granting access straight away.
     */
    public function testChangedUserPermissionsAreMergedAgain()
    {
        $user = $this->makeUser([], ['admins.manage' => 1]);
        $this->assertTrue($user->hasPermission('admins.manage'));

        $user->permissions = ['admins.manage' => -1];

        $this->assertFalse($user->hasPermission('admins.manage'));
    }

    /**
     * testChangedRoleIsMergedAgain ensures a new role id drops the loaded role and the permissions merged from it.
     */
    public function testChangedRoleIsMergedAgain()
    {
        $user = $this->makeUser(['admins.manage' => 1], []);
        $this->assertTrue($user->hasPermission('admins.manage'));

        $user->role_id = 2;
        $this->assertFalse($user->relationLoaded('role'));

        // Stands in for the lazy load of the new role
        $user->setRelation('role', $this->makeRole(2, []));

        $this->assertFalse($user->hasPermission('admins.manage'));
    }

    /**
     * testReassignedRoleIsMergedAgain ensures assigning a role instance with the same id, as associate() does, merges its permissions.
     */
    public function testReassignedRoleIsMergedAgain()
    {
        $user = $this->makeUser(['admins.manage' => 1], []);
        $this->assertTrue($user->hasPermission('admins.manage'));

        $user->role_id = 1;
        $user->setRelation('role', $this->makeRole(1, []));

        $this->assertFalse($user->hasPermission('admins.manage'));
    }

    /**
     * makeUser returns a user with the given role and user permissions, without touching the database.
     */
    protected function makeUser(array $rolePermissions, array $userPermissions): User
    {
        $user = new User;
        $user->setRawAttributes(['role_id' => 1, 'permissions' => json_encode($userPermissions)]);
        $user->setRelation('role', $this->makeRole(1, $rolePermissions));

        return $user;
    }

    /**
     * makeRole returns a role with the given id and permissions, without touching the database.
     */
    protected function makeRole(int $id, array $permissions): Role
    {
        $role = new Role;
        $role->setRawAttributes(['id' => $id, 'permissions' => json_encode($permissions)]);

        return $role;
    }
}
