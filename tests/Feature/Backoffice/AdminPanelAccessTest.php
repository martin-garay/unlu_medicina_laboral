<?php

namespace Tests\Feature\Backoffice;

use App\Models\User;
use Database\Seeders\BackofficeRolesAndPermissionsSeeder;
use Filament\Facades\Filament;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class AdminPanelAccessTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        (require database_path('migrations/2026_04_27_000008_create_users_table.php'))->up();
        (require database_path('migrations/2026_04_28_173621_create_permission_tables.php'))->up();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function test_admin_login_page_renders_successfully(): void
    {
        $this->get('/admin/login')
            ->assertOk();
    }

    public function test_admin_panel_redirects_guests_to_login(): void
    {
        $this->get('/admin')
            ->assertRedirect('/admin/login');
    }

    public function test_user_panel_access_requires_backoffice_permission(): void
    {
        config()->set('backoffice.local_admin.enabled', false);
        $this->seed(BackofficeRolesAndPermissionsSeeder::class);

        $panel = Filament::getPanel('admin');
        $userWithoutPermission = User::create([
            'name' => 'Operador',
            'email' => 'operador@example.test',
            'password' => 'secret',
            'is_admin' => true,
        ]);
        $userWithPermission = User::create([
            'name' => 'Admin',
            'email' => 'admin@example.test',
            'password' => 'secret',
            'is_admin' => false,
        ]);

        $userWithPermission->assignRole('admin');

        $this->assertFalse($userWithoutPermission->canAccessPanel($panel));
        $this->assertTrue($userWithPermission->canAccessPanel($panel));
    }

    public function test_authenticated_user_without_permission_cannot_access_panel(): void
    {
        $user = User::create([
            'name' => 'Operador',
            'email' => 'operador@example.test',
            'password' => 'secret',
            'is_admin' => true,
        ]);

        $this->actingAs($user)
            ->get('/admin')
            ->assertForbidden();
    }

    public function test_authenticated_user_with_backoffice_permission_can_access_panel(): void
    {
        config()->set('backoffice.local_admin.enabled', false);
        $this->seed(BackofficeRolesAndPermissionsSeeder::class);

        $user = User::create([
            'name' => 'Admin',
            'email' => 'admin@example.test',
            'password' => 'secret',
            'is_admin' => false,
        ]);
        $user->assignRole('admin');

        $this->actingAs($user)
            ->get('/admin')
            ->assertOk();
    }
}
