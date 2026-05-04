<?php

namespace Tests\Feature\Backoffice;

use App\Filament\Resources\AuditoriaAdministrativaResource;
use App\Filament\Resources\AuditoriaAdministrativaResource\Pages\ListAuditoriaAdministrativa;
use App\Models\AuditoriaAdministrativa;
use App\Models\User;
use Database\Seeders\BackofficeRolesAndPermissionsSeeder;
use Filament\Facades\Filament;
use Illuminate\Support\Str;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class AuditoriaAdministrativaResourceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        (require database_path('migrations/2026_04_27_000008_create_users_table.php'))->up();
        (require database_path('migrations/2026_04_28_173621_create_permission_tables.php'))->up();
        (require database_path('migrations/2026_04_28_230300_create_auditoria_administrativa_table.php'))->up();

        config()->set('backoffice.local_admin.enabled', false);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->seed(BackofficeRolesAndPermissionsSeeder::class);

        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_user_without_backoffice_access_cannot_access_auditoria_resource(): void
    {
        $user = $this->createUser();

        $this->actingAs($user)
            ->get(AuditoriaAdministrativaResource::getUrl('index'))
            ->assertForbidden();
    }

    public function test_user_with_backoffice_access_but_without_auditoria_permission_cannot_access_resource(): void
    {
        $user = $this->createUser();
        $user->givePermissionTo(Permission::findByName('backoffice.access'));

        $this->actingAs($user)
            ->get(AuditoriaAdministrativaResource::getUrl('index'))
            ->assertForbidden();
    }

    #[DataProvider('rolesWithAuditoriaAccessProvider')]
    public function test_roles_with_auditoria_permission_can_access_resource(string $roleName): void
    {
        $user = $this->createUser();
        $user->assignRole($roleName);

        $this->actingAs($user)
            ->get(AuditoriaAdministrativaResource::getUrl('index'))
            ->assertOk();
    }

    public function test_director_cannot_access_auditoria_resource(): void
    {
        $user = $this->createUser();
        $user->assignRole('director');

        $this->actingAs($user)
            ->get(AuditoriaAdministrativaResource::getUrl('index'))
            ->assertForbidden();
    }

    public function test_authorized_user_can_see_auditoria_records_on_list_page(): void
    {
        $user = $this->createUser();
        $user->assignRole('admin');
        $actor = $this->createUser(['name' => 'Operador Auditor']);
        $evento = $this->createAuditEvent($actor, [
            'action' => 'avisos.viewed',
            'origin' => 'filament',
            'auditable_type' => 'App\\Models\\Aviso',
            'auditable_id' => 10,
        ]);

        Livewire::actingAs($user)
            ->test(ListAuditoriaAdministrativa::class)
            ->assertCanSeeTableRecords([$evento])
            ->assertSee('Operador Auditor')
            ->assertSee('avisos.viewed')
            ->assertSee('filament')
            ->assertSee('App\\Models\\Aviso')
            ->assertSee('10');
    }

    public function test_authorized_user_can_filter_auditoria_records(): void
    {
        $user = $this->createUser();
        $user->assignRole('admin');
        $actor = $this->createUser(['name' => 'Actor Principal']);
        $otroActor = $this->createUser(['name' => 'Actor Secundario']);
        $evento = $this->createAuditEvent($actor, [
            'action' => 'certificados.viewed',
            'origin' => 'filament',
            'auditable_type' => 'App\\Models\\AnticipoCertificado',
            'created_at' => now()->setDate(2026, 4, 10)->setTime(9, 0),
        ]);
        $otroEvento = $this->createAuditEvent($otroActor, [
            'action' => 'roles.seeded',
            'origin' => 'command',
            'auditable_type' => 'App\\Models\\User',
            'created_at' => now()->setDate(2026, 3, 5)->setTime(9, 0),
        ]);

        Livewire::actingAs($user)
            ->test(ListAuditoriaAdministrativa::class)
            ->assertTableFilterExists('actor_user_id')
            ->assertTableFilterExists('action')
            ->assertTableFilterExists('origin')
            ->assertTableFilterExists('auditable_type')
            ->assertTableFilterExists('created_at')
            ->filterTable('actor_user_id', $actor->id)
            ->assertCanSeeTableRecords([$evento])
            ->assertCanNotSeeTableRecords([$otroEvento]);

        Livewire::actingAs($user)
            ->test(ListAuditoriaAdministrativa::class)
            ->filterTable('action', 'roles.seeded')
            ->assertCanSeeTableRecords([$otroEvento])
            ->assertCanNotSeeTableRecords([$evento]);

        Livewire::actingAs($user)
            ->test(ListAuditoriaAdministrativa::class)
            ->filterTable('origin', 'filament')
            ->assertCanSeeTableRecords([$evento])
            ->assertCanNotSeeTableRecords([$otroEvento]);

        Livewire::actingAs($user)
            ->test(ListAuditoriaAdministrativa::class)
            ->filterTable('auditable_type', 'App\\Models\\User')
            ->assertCanSeeTableRecords([$otroEvento])
            ->assertCanNotSeeTableRecords([$evento]);

        Livewire::actingAs($user)
            ->test(ListAuditoriaAdministrativa::class)
            ->filterTable('created_at', ['desde' => '2026-04-01', 'hasta' => '2026-04-30'])
            ->assertCanSeeTableRecords([$evento])
            ->assertCanNotSeeTableRecords([$otroEvento]);
    }

    public function test_authorized_user_can_search_auditoria_records(): void
    {
        $user = $this->createUser();
        $user->assignRole('admin');
        $actor = $this->createUser(['name' => 'Actor Busqueda']);
        $evento = $this->createAuditEvent($actor, [
            'action' => 'auditoria.unique_action',
            'origin' => 'filament',
            'auditable_type' => 'App\\Models\\Conversacion',
            'auditable_id' => 1234,
        ]);
        $otroEvento = $this->createAuditEvent(null, [
            'action' => 'auditoria.other_action',
            'origin' => 'system',
            'auditable_type' => 'App\\Models\\Aviso',
            'auditable_id' => 5678,
        ]);

        Livewire::actingAs($user)
            ->test(ListAuditoriaAdministrativa::class)
            ->searchTable('auditoria.unique_action')
            ->assertCanSeeTableRecords([$evento])
            ->assertCanNotSeeTableRecords([$otroEvento]);

        Livewire::actingAs($user)
            ->test(ListAuditoriaAdministrativa::class)
            ->searchTable('App\\Models\\Aviso')
            ->assertCanSeeTableRecords([$otroEvento])
            ->assertCanNotSeeTableRecords([$evento]);

        Livewire::actingAs($user)
            ->test(ListAuditoriaAdministrativa::class)
            ->searchTable('1234')
            ->assertCanSeeTableRecords([$evento])
            ->assertCanNotSeeTableRecords([$otroEvento]);
    }

    public function test_auditoria_detail_shows_before_after_and_metadata(): void
    {
        $user = $this->createUser();
        $user->assignRole('admin');
        $actor = $this->createUser(['name' => 'Auditor Detalle', 'email' => 'auditor-detalle@example.test']);
        $evento = $this->createAuditEvent($actor, [
            'action' => 'avisos.updated',
            'origin' => 'filament',
            'auditable_type' => 'App\\Models\\Aviso',
            'auditable_id' => 55,
            'before_values' => ['estado' => 'inicial'],
            'after_values' => ['estado' => 'observado'],
            'metadata' => ['motivo' => 'control administrativo'],
        ]);

        Livewire::actingAs($user)
            ->test(ListAuditoriaAdministrativa::class)
            ->assertTableActionVisible('view', $evento)
            ->assertTableActionHasIcon('view', 'heroicon-o-eye', $evento)
            ->assertTableActionHasLabel('view', 'Ver detalle', $evento)
            ->assertTableActionHasUrl(
                'view',
                AuditoriaAdministrativaResource::getUrl('view', ['record' => $evento]),
                $evento,
            );

        $this->actingAs($user)
            ->get(AuditoriaAdministrativaResource::getUrl('view', ['record' => $evento]))
            ->assertOk()
            ->assertSee('Volver')
            ->assertSee('Auditor Detalle')
            ->assertSee('auditor-detalle@example.test')
            ->assertSee('avisos.updated')
            ->assertSee('filament')
            ->assertSee('App\\Models\\Aviso')
            ->assertSee('55')
            ->assertSee('Before values')
            ->assertSee('After values')
            ->assertSee('Metadata')
            ->assertSee('inicial')
            ->assertSee('observado')
            ->assertSee('control administrativo');
    }

    public function test_user_without_auditoria_permission_cannot_access_detail_by_direct_url(): void
    {
        $user = $this->createUser();
        $user->givePermissionTo(Permission::findByName('backoffice.access'));
        $evento = $this->createAuditEvent(null);

        $this->actingAs($user)
            ->get(AuditoriaAdministrativaResource::getUrl('view', ['record' => $evento]))
            ->assertForbidden();
    }

    public function test_auditoria_resource_is_read_only(): void
    {
        $user = $this->createUser();
        $user->assignRole('admin');
        $evento = $this->createAuditEvent($user);

        $this->actingAs($user);

        $this->assertTrue(AuditoriaAdministrativaResource::canViewAny());
        $this->assertTrue(AuditoriaAdministrativaResource::canView($evento));
        $this->assertFalse(AuditoriaAdministrativaResource::canCreate());
        $this->assertFalse(AuditoriaAdministrativaResource::canEdit($evento));
        $this->assertFalse(AuditoriaAdministrativaResource::canDelete($evento));
        $this->assertFalse(AuditoriaAdministrativaResource::canDeleteAny());
    }

    public static function rolesWithAuditoriaAccessProvider(): array
    {
        return [
            'admin' => ['admin'],
            'auditor' => ['auditor'],
        ];
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

    private function createAuditEvent(?User $actor, array $attributes = []): AuditoriaAdministrativa
    {
        $createdAt = $attributes['created_at'] ?? null;

        $evento = AuditoriaAdministrativa::create([
            'actor_user_id' => $actor?->id,
            'action' => $attributes['action'] ?? 'auditoria.test',
            'origin' => $attributes['origin'] ?? 'filament',
            'auditable_type' => $attributes['auditable_type'] ?? null,
            'auditable_id' => $attributes['auditable_id'] ?? null,
            'before_values' => $attributes['before_values'] ?? null,
            'after_values' => $attributes['after_values'] ?? null,
            'metadata' => $attributes['metadata'] ?? null,
        ]);

        if ($createdAt) {
            $evento->created_at = $createdAt;
            $evento->save();
        }

        return $evento;
    }
}
