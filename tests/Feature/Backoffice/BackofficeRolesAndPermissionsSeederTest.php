<?php

namespace Tests\Feature\Backoffice;

use App\Models\User;
use Database\Seeders\BackofficeRolesAndPermissionsSeeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class BackofficeRolesAndPermissionsSeederTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        (require database_path('migrations/2026_04_27_000008_create_users_table.php'))->up();
        (require database_path('migrations/2026_04_28_173621_create_permission_tables.php'))->up();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function test_seeder_creates_roles_and_permissions_idempotently(): void
    {
        config()->set('backoffice.local_admin.enabled', false);

        $this->seed(BackofficeRolesAndPermissionsSeeder::class);
        $this->seed(BackofficeRolesAndPermissionsSeeder::class);

        $this->assertSame(count(config('backoffice.permissions')), Permission::query()->count());
        $this->assertSame(count(config('backoffice.roles')), Role::query()->count());

        $admin = Role::findByName('admin', 'web');
        $auditor = Role::findByName('auditor', 'web');
        $director = Role::findByName('director', 'web');

        $this->assertTrue($admin->hasPermissionTo('users.manage'));
        $this->assertTrue($auditor->hasPermissionTo('auditoria.view'));
        $this->assertFalse($auditor->hasPermissionTo('users.manage'));
        $this->assertTrue($director->hasPermissionTo('reportes.view'));
        $this->assertFalse($director->hasPermissionTo('auditoria.view'));
    }

    public function test_seeder_can_create_local_admin_user(): void
    {
        config()->set('backoffice.local_admin.enabled', true);
        config()->set('backoffice.local_admin.email', 'admin@admin.com');
        config()->set('backoffice.local_admin.password', 'admin123456');

        $this->seed(BackofficeRolesAndPermissionsSeeder::class);
        $this->seed(BackofficeRolesAndPermissionsSeeder::class);

        $user = User::query()->where('email', 'admin@admin.com')->firstOrFail();

        $this->assertSame(1, User::query()->where('email', 'admin@admin.com')->count());
        $this->assertTrue($user->hasRole('admin'));
        $this->assertTrue($user->can('backoffice.access'));
        $this->assertTrue($user->is_admin);
        $this->assertNotSame('admin123456', $user->password);
    }
}
