<?php

namespace Tests\Feature\Backoffice;

use App\Filament\Resources\AvisoResource;
use App\Filament\Resources\AvisoResource\Pages\ListAvisos;
use App\Models\Aviso;
use App\Models\User;
use Database\Seeders\BackofficeRolesAndPermissionsSeeder;
use Filament\Facades\Filament;
use Illuminate\Support\Str;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\Concerns\CreatesTestingSchema;
use Tests\TestCase;

class AvisoResourceTest extends TestCase
{
    use CreatesTestingSchema;

    protected function setUp(): void
    {
        parent::setUp();

        (require database_path('migrations/2026_04_27_000008_create_users_table.php'))->up();
        (require database_path('migrations/2026_04_28_173621_create_permission_tables.php'))->up();
        $this->createTestingSchema();

        config()->set('backoffice.local_admin.enabled', false);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->seed(BackofficeRolesAndPermissionsSeeder::class);

        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_user_without_backoffice_access_cannot_access_avisos_resource(): void
    {
        $user = $this->createUser();

        $this->actingAs($user)
            ->get(AvisoResource::getUrl('index'))
            ->assertForbidden();
    }

    public function test_user_with_backoffice_access_but_without_avisos_permission_cannot_access_resource(): void
    {
        $user = $this->createUser();
        $user->givePermissionTo(Permission::findByName('backoffice.access'));

        $this->actingAs($user)
            ->get(AvisoResource::getUrl('index'))
            ->assertForbidden();
    }

    #[DataProvider('rolesWithAvisosAccessProvider')]
    public function test_roles_with_avisos_permission_can_access_resource(string $roleName): void
    {
        $user = $this->createUser();
        $user->assignRole($roleName);

        $this->actingAs($user)
            ->get(AvisoResource::getUrl('index'))
            ->assertOk();
    }

    public function test_authorized_user_can_see_aviso_records_on_list_page_without_certificate_payload(): void
    {
        $user = $this->createUser();
        $user->assignRole('admin');
        $aviso = $this->createAviso([
            'dni' => '30111222',
            'nombre_completo' => 'Ana Laboral',
            'legajo' => '12345',
            'wa_number' => '5491111111111',
            'certificado_base64' => 'NO_MOSTRAR_CERTIFICADO_BASE64',
        ]);
        $otroAviso = $this->createAviso([
            'dni' => '30222333',
            'nombre_completo' => 'Luis Auditor',
            'legajo' => '67890',
            'wa_number' => '5492222222222',
        ]);

        Livewire::actingAs($user)
            ->test(ListAvisos::class)
            ->assertCanSeeTableRecords([$aviso, $otroAviso])
            ->assertCountTableRecords(2)
            ->assertSee('30111222')
            ->assertSee('Ana Laboral')
            ->assertSee('12345')
            ->assertSee('5491111111111')
            ->assertDontSee('NO_MOSTRAR_CERTIFICADO_BASE64')
            ->assertDontSee('certificado_base64');
    }

    public function test_authorized_user_can_search_avisos_by_main_fields(): void
    {
        $user = $this->createUser();
        $user->assignRole('admin');
        $aviso = $this->createAviso([
            'dni' => '30111222',
            'nombre_completo' => 'Ana Laboral',
            'legajo' => '12345',
            'wa_number' => '5491111111111',
        ]);
        $otroAviso = $this->createAviso([
            'dni' => '30222333',
            'nombre_completo' => 'Luis Auditor',
            'legajo' => '67890',
            'wa_number' => '5492222222222',
        ]);

        Livewire::actingAs($user)
            ->test(ListAvisos::class)
            ->searchTable('Ana Laboral')
            ->assertCanSeeTableRecords([$aviso])
            ->assertCanNotSeeTableRecords([$otroAviso]);

        Livewire::actingAs($user)
            ->test(ListAvisos::class)
            ->searchTable('67890')
            ->assertCanSeeTableRecords([$otroAviso])
            ->assertCanNotSeeTableRecords([$aviso]);

        Livewire::actingAs($user)
            ->test(ListAvisos::class)
            ->searchTable('5491111111111')
            ->assertCanSeeTableRecords([$aviso])
            ->assertCanNotSeeTableRecords([$otroAviso]);

        Livewire::actingAs($user)
            ->test(ListAvisos::class)
            ->searchTable('30222333')
            ->assertCanSeeTableRecords([$otroAviso])
            ->assertCanNotSeeTableRecords([$aviso]);
    }

    public function test_authorized_user_can_filter_avisos_by_basic_fields(): void
    {
        $user = $this->createUser();
        $user->assignRole('admin');
        $aviso = $this->createAviso([
            'estado' => 'inicial',
            'tipo' => 'ausencia',
            'tipo_ausentismo' => 'enfermedad',
            'sede' => 'Lujan',
        ]);
        $otroAviso = $this->createAviso([
            'estado' => 'registrado',
            'tipo' => 'familiar',
            'tipo_ausentismo' => 'familiar',
            'sede' => 'San Miguel',
        ]);

        Livewire::actingAs($user)
            ->test(ListAvisos::class)
            ->assertTableFilterExists('estado')
            ->assertTableFilterExists('tipo')
            ->assertTableFilterExists('tipo_ausentismo')
            ->assertTableFilterExists('sede')
            ->filterTable('estado', 'inicial')
            ->assertCanSeeTableRecords([$aviso])
            ->assertCanNotSeeTableRecords([$otroAviso]);

        Livewire::actingAs($user)
            ->test(ListAvisos::class)
            ->filterTable('tipo', 'familiar')
            ->assertCanSeeTableRecords([$otroAviso])
            ->assertCanNotSeeTableRecords([$aviso]);

        Livewire::actingAs($user)
            ->test(ListAvisos::class)
            ->filterTable('tipo_ausentismo', 'enfermedad')
            ->assertCanSeeTableRecords([$aviso])
            ->assertCanNotSeeTableRecords([$otroAviso]);

        Livewire::actingAs($user)
            ->test(ListAvisos::class)
            ->filterTable('sede', 'San Miguel')
            ->assertCanSeeTableRecords([$otroAviso])
            ->assertCanNotSeeTableRecords([$aviso]);
    }

    public function test_avisos_resource_is_read_only(): void
    {
        $user = $this->createUser();
        $user->assignRole('admin');
        $aviso = $this->createAviso();

        $this->actingAs($user);

        $this->assertTrue(AvisoResource::canViewAny());
        $this->assertTrue(AvisoResource::canView($aviso));
        $this->assertFalse(AvisoResource::canCreate());
        $this->assertFalse(AvisoResource::canEdit($aviso));
        $this->assertFalse(AvisoResource::canDelete($aviso));
        $this->assertFalse(AvisoResource::canDeleteAny());
    }

    public static function rolesWithAvisosAccessProvider(): array
    {
        return [
            'admin' => ['admin'],
            'auditor' => ['auditor'],
            'director' => ['director'],
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

    private function createAviso(array $attributes = []): Aviso
    {
        return Aviso::create([
            'dni' => $attributes['dni'] ?? '30123456',
            'nombre_completo' => $attributes['nombre_completo'] ?? 'Persona de Prueba',
            'legajo' => $attributes['legajo'] ?? '12345',
            'sede' => $attributes['sede'] ?? 'Lujan',
            'jornada_laboral' => $attributes['jornada_laboral'] ?? 'Completa',
            'tipo' => $attributes['tipo'] ?? 'ausencia',
            'estado' => $attributes['estado'] ?? 'inicial',
            'tipo_ausentismo' => $attributes['tipo_ausentismo'] ?? 'enfermedad',
            'fecha_inicio' => $attributes['fecha_inicio'] ?? now()->toDateString(),
            'fecha_fin' => $attributes['fecha_fin'] ?? now()->addDay()->toDateString(),
            'cantidad_dias' => $attributes['cantidad_dias'] ?? 2,
            'certificado_base64' => $attributes['certificado_base64'] ?? null,
            'motivo' => $attributes['motivo'] ?? 'Motivo de prueba',
            'domicilio_circunstancial' => $attributes['domicilio_circunstancial'] ?? null,
            'observaciones' => $attributes['observaciones'] ?? null,
            'wa_number' => $attributes['wa_number'] ?? '5491112345678',
            'metadata' => $attributes['metadata'] ?? null,
        ]);
    }
}
