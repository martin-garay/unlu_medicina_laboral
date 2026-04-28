<?php

namespace Tests\Feature\Backoffice;

use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class PermissionStackTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        (require database_path('migrations/2026_04_27_000008_create_users_table.php'))->up();
        (require database_path('migrations/2026_04_28_173621_create_permission_tables.php'))->up();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function test_user_can_receive_role_with_backoffice_permission(): void
    {
        $permission = Permission::create([
            'name' => 'backoffice.access',
            'guard_name' => 'web',
        ]);

        $role = Role::create([
            'name' => 'admin',
            'guard_name' => 'web',
        ]);

        $role->givePermissionTo($permission);

        $user = User::create([
            'name' => 'Admin',
            'email' => 'admin@example.test',
            'password' => 'secret',
            'is_admin' => true,
        ]);

        $user->assignRole($role);

        $this->assertTrue($user->hasRole('admin'));
        $this->assertTrue($user->can('backoffice.access'));
    }

    public function test_permission_stack_uses_web_guard(): void
    {
        $role = Role::create([
            'name' => 'auditor',
            'guard_name' => 'web',
        ]);

        $this->assertSame('web', $role->guard_name);
        $this->assertSame('users', config('auth.guards.web.provider'));
    }
}
