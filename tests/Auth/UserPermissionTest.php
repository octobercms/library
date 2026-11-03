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
     * makeUser returns a user with the given role and user permissions, without touching the database.
     */
    protected function makeUser(array $rolePermissions, array $userPermissions): User
    {
        $role = new Role;
        $role->setRawAttributes(['permissions' => json_encode($rolePermissions)]);

        $user = new User;
        $user->setRawAttributes(['permissions' => json_encode($userPermissions)]);
        $user->setRelation('role', $role);

        return $user;
    }
}
