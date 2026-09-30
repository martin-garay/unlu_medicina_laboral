<?php

namespace Tests\Feature\Backoffice;

use App\Filament\Resources\SedeResource;
use App\Filament\Resources\SedeResource\Pages\CreateSede;
use App\Filament\Resources\SedeResource\Pages\EditSede;
use App\Models\Sede;
use App\Models\User;
use Database\Seeders\BackofficeRolesAndPermissionsSeeder;
use Database\Seeders\ChatCatalogsSeeder;
use Filament\Facades\Filament;
use Livewire\Livewire;
use Tests\TestCase;

class CatalogoResourceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        (require database_path('migrations/2026_04_27_000008_create_users_table.php'))->up();
        (require database_path('migrations/2026_04_28_173621_create_permission_tables.php'))->up();
        (require database_path('migrations/2026_04_28_230300_create_auditoria_administrativa_table.php'))->up();
        (require database_path('migrations/2026_09_30_000009_create_chat_catalogs_tables.php'))->up();
        $this->seed(BackofficeRolesAndPermissionsSeeder::class);
        $this->seed(ChatCatalogsSeeder::class);
        config()->set('backoffice.local_admin.enabled', false);
        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_admin_can_create_catalog_entry_and_change_is_audited(): void
    {
        $admin = $this->userWithRole('admin');

        $this->actingAs($admin)->get(SedeResource::getUrl('index'))->assertOk();

        Livewire::actingAs($admin)
            ->test(CreateSede::class)
            ->set('data.codigo', 'sede_nueva')
            ->set('data.nombre', 'Sede Nueva')
            ->set('data.activo', true)
            ->set('data.orden', 40)
            ->call('create')
            ->assertHasNoFormErrors();

        $sede = Sede::query()->where('codigo', 'sede_nueva')->firstOrFail();
        $this->assertDatabaseHas('auditoria_administrativa', [
            'action' => 'catalogo.created',
            'auditable_type' => Sede::class,
            'auditable_id' => $sede->id,
            'actor_user_id' => $admin->id,
        ]);
    }

    public function test_auditor_can_view_but_cannot_create_catalog_entries(): void
    {
        $auditor = $this->userWithRole('auditor');

        $this->actingAs($auditor)->get(SedeResource::getUrl('index'))->assertOk();
        $this->actingAs($auditor)->get(SedeResource::getUrl('create'))->assertForbidden();
    }

    public function test_admin_can_edit_and_deactivate_without_changing_the_stable_code(): void
    {
        $admin = $this->userWithRole('admin');
        $sede = Sede::query()->where('codigo', 'central')->firstOrFail();

        Livewire::actingAs($admin)
            ->test(EditSede::class, ['record' => $sede->getRouteKey()])
            ->set('data.nombre', 'Centro actualizado')
            ->set('data.activo', false)
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('sedes', [
            'id' => $sede->id,
            'codigo' => 'central',
            'nombre' => 'Centro actualizado',
            'activo' => false,
        ]);
        $this->assertDatabaseHas('auditoria_administrativa', [
            'action' => 'catalogo.updated',
            'auditable_type' => Sede::class,
            'auditable_id' => $sede->id,
            'actor_user_id' => $admin->id,
        ]);
    }

    public function test_seed_is_idempotent_and_does_not_overwrite_admin_edits(): void
    {
        Sede::query()->where('codigo', 'central')->update(['nombre' => 'Nombre administrado']);

        $this->seed(ChatCatalogsSeeder::class);

        $this->assertSame('Nombre administrado', Sede::query()->where('codigo', 'central')->value('nombre'));
        $this->assertSame(3, Sede::query()->count());
    }

    private function userWithRole(string $role): User
    {
        $user = User::query()->create([
            'name' => ucfirst($role),
            'email' => $role.'@example.test',
            'password' => 'secret',
        ]);
        $user->assignRole($role);

        return $user;
    }
}
