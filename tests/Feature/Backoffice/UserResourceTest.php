<?php

namespace Tests\Feature\Backoffice;

use App\Filament\Resources\UserResource;
use App\Filament\Resources\UserResource\Pages\ListUsers;
use App\Models\User;
use Database\Seeders\BackofficeRolesAndPermissionsSeeder;
use Filament\Facades\Filament;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class UserResourceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        (require database_path('migrations/2026_04_27_000008_create_users_table.php'))->up();
        (require database_path('migrations/2026_04_28_173621_create_permission_tables.php'))->up();

        config()->set('backoffice.local_admin.enabled', false);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->seed(BackofficeRolesAndPermissionsSeeder::class);

        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_user_without_backoffice_access_cannot_access_users_resource(): void
    {
        $user = $this->createUser();

        $this->actingAs($user)
            ->get(UserResource::getUrl('index'))
            ->assertForbidden();
    }

    public function test_user_with_backoffice_access_but_without_users_permission_cannot_access_resource(): void
    {
        $user = $this->createUser();
        $user->givePermissionTo(Permission::findByName('backoffice.access'));

        $this->actingAs($user)
            ->get(UserResource::getUrl('index'))
            ->assertForbidden();
    }

    public function test_admin_can_access_users_resource(): void
    {
        $user = $this->createUser();
        $user->assignRole('admin');

        $this->actingAs($user)
            ->get(UserResource::getUrl('index'))
            ->assertOk();
    }

    public function test_auditor_cannot_access_users_resource(): void
    {
        $auditor = $this->createUser(['email' => 'auditor@example.test']);
        $auditor->assignRole('auditor');

        $this->actingAs($auditor)
            ->get(UserResource::getUrl('index'))
            ->assertForbidden();
    }

    public function test_director_cannot_access_users_resource(): void
    {
        $director = $this->createUser(['email' => 'director@example.test']);
        $director->assignRole('director');

        $this->actingAs($director)
            ->get(UserResource::getUrl('index'))
            ->assertForbidden();
    }

    public function test_authorized_user_can_see_users_on_list_page_without_passwords(): void
    {
        $admin = $this->createUser();
        $admin->assignRole('admin');
        $user = $this->createUser([
            'name' => 'Usuario Operativo',
            'email' => 'operativo@example.test',
            'password' => 'NO_MOSTRAR_PASSWORD',
            'is_admin' => true,
        ]);
        $user->assignRole('auditor');

        Livewire::actingAs($admin)
            ->test(ListUsers::class)
            ->assertCanSeeTableRecords([$admin, $user])
            ->assertSee('Usuario Operativo')
            ->assertSee('operativo@example.test')
            ->assertSee('auditor')
            ->assertDontSee('NO_MOSTRAR_PASSWORD')
            ->assertDontSee('password')
            ->assertDontSee('remember_token');
    }

    public function test_authorized_user_can_search_users_by_name_and_email(): void
    {
        $admin = $this->createUser();
        $admin->assignRole('admin');
        $user = $this->createUser([
            'name' => 'Usuario Buscado',
            'email' => 'buscado@example.test',
        ]);
        $otroUsuario = $this->createUser([
            'name' => 'Otro Usuario',
            'email' => 'otro@example.test',
        ]);

        Livewire::actingAs($admin)
            ->test(ListUsers::class)
            ->searchTable('Usuario Buscado')
            ->assertCanSeeTableRecords([$user])
            ->assertCanNotSeeTableRecords([$otroUsuario]);

        Livewire::actingAs($admin)
            ->test(ListUsers::class)
            ->searchTable('otro@example.test')
            ->assertCanSeeTableRecords([$otroUsuario])
            ->assertCanNotSeeTableRecords([$user]);
    }

    public function test_authorized_user_can_filter_users_by_role(): void
    {
        $admin = $this->createUser();
        $admin->assignRole('admin');
        $auditorUser = $this->createUser(['email' => 'auditor-user@example.test']);
        $auditorUser->assignRole('auditor');
        $directorUser = $this->createUser(['email' => 'director-user@example.test']);
        $directorUser->assignRole('director');
        $auditorRole = Role::findByName('auditor');

        Livewire::actingAs($admin)
            ->test(ListUsers::class)
            ->assertTableFilterExists('roles')
            ->filterTable('roles', $auditorRole->id)
            ->assertCanSeeTableRecords([$auditorUser])
            ->assertCanNotSeeTableRecords([$directorUser]);
    }

    public function test_authorized_user_sees_detail_action_and_can_access_user_detail(): void
    {
        $admin = $this->createUser();
        $admin->assignRole('admin');
        $user = $this->createUser([
            'name' => 'Usuario Detalle',
            'email' => 'detalle@example.test',
            'password' => 'NO_MOSTRAR_PASSWORD_DETALLE',
        ]);
        $user->assignRole('auditor');

        Livewire::actingAs($admin)
            ->test(ListUsers::class)
            ->assertTableActionVisible('view', $user)
            ->assertTableActionHasIcon('view', 'heroicon-o-eye', $user)
            ->assertTableActionHasLabel('view', 'Ver detalle', $user)
            ->assertTableActionHasUrl(
                'view',
                UserResource::getUrl('view', ['record' => $user]),
                $user,
            );

        $this->actingAs($admin)
            ->get(UserResource::getUrl('view', ['record' => $user]))
            ->assertOk()
            ->assertSee('Volver')
            ->assertSee('Usuario Detalle')
            ->assertSee('detalle@example.test')
            ->assertSee('auditor')
            ->assertDontSee('NO_MOSTRAR_PASSWORD_DETALLE')
            ->assertDontSee('password')
            ->assertDontSee('remember_token');
    }

    public function test_user_without_users_permission_cannot_access_user_detail_by_direct_url(): void
    {
        $user = $this->createUser();
        $user->givePermissionTo(Permission::findByName('backoffice.access'));
        $target = $this->createUser(['email' => 'target@example.test']);

        $this->actingAs($user)
            ->get(UserResource::getUrl('view', ['record' => $target]))
            ->assertForbidden();
    }

    public function test_users_resource_is_read_only(): void
    {
        $admin = $this->createUser();
        $admin->assignRole('admin');
        $user = $this->createUser(['email' => 'readonly@example.test']);

        $this->actingAs($admin);

        $this->assertTrue(UserResource::canViewAny());
        $this->assertTrue(UserResource::canView($user));
        $this->assertFalse(UserResource::canCreate());
        $this->assertFalse(UserResource::canEdit($user));
        $this->assertFalse(UserResource::canDelete($user));
        $this->assertFalse(UserResource::canDeleteAny());
    }

    private function createUser(array $attributes = []): User
    {
        return User::create([
            'name' => $attributes['name'] ?? 'Usuario Backoffice',
            'email' => $attributes['email'] ?? Str::uuid() . '@example.test',
            'password' => $attributes['password'] ?? 'secret',
            'is_admin' => $attributes['is_admin'] ?? false,
        ]);
    }
}
