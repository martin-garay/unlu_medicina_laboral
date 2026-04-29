<?php

namespace Tests\Feature\Backoffice;

use App\Filament\Resources\AnticipoCertificadoResource;
use App\Filament\Resources\AnticipoCertificadoResource\Pages\ListAnticipoCertificados;
use App\Models\AnticipoCertificado;
use App\Models\AnticipoCertificadoArchivo;
use App\Models\Aviso;
use App\Models\Conversacion;
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

class AnticipoCertificadoResourceTest extends TestCase
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

    public function test_user_without_backoffice_access_cannot_access_certificados_resource(): void
    {
        $user = $this->createUser();

        $this->actingAs($user)
            ->get(AnticipoCertificadoResource::getUrl('index'))
            ->assertForbidden();
    }

    public function test_user_with_backoffice_access_but_without_certificados_permission_cannot_access_resource(): void
    {
        $user = $this->createUser();
        $user->givePermissionTo(Permission::findByName('backoffice.access'));

        $this->actingAs($user)
            ->get(AnticipoCertificadoResource::getUrl('index'))
            ->assertForbidden();
    }

    #[DataProvider('rolesWithCertificadosAccessProvider')]
    public function test_roles_with_certificados_permission_can_access_resource(string $roleName): void
    {
        $user = $this->createUser();
        $user->assignRole($roleName);

        $this->actingAs($user)
            ->get(AnticipoCertificadoResource::getUrl('index'))
            ->assertOk();
    }

    public function test_authorized_user_can_see_certificado_records_without_file_access_actions(): void
    {
        $user = $this->createUser();
        $user->assignRole('admin');
        [$conversacion, $aviso] = $this->createConversationAndAviso();
        $anticipo = $this->createAnticipo($conversacion, $aviso, [
            'numero_anticipo' => 'ANT-0001',
            'wa_number' => '5491111111111',
            'nombre_completo' => 'Ana Laboral',
            'legajo' => '12345',
            'sede' => 'Lujan',
            'tipo_certificado' => 'medico',
            'estado' => 'recibido',
            'metadata' => ['secret' => 'NO_MOSTRAR_METADATA_ANTICIPO'],
        ]);
        $this->createArchivo($conversacion, $anticipo, [
            'storage_path' => 'certificados/privado/no-mostrar.pdf',
        ]);

        Livewire::actingAs($user)
            ->test(ListAnticipoCertificados::class)
            ->assertCanSeeTableRecords([$anticipo])
            ->assertCountTableRecords(1)
            ->assertSee('ANT-0001')
            ->assertSee('Ana Laboral')
            ->assertSee('12345')
            ->assertSee('medico')
            ->assertSee('recibido')
            ->assertDontSee('NO_MOSTRAR_METADATA_ANTICIPO')
            ->assertDontSee('certificados/privado/no-mostrar.pdf')
            ->assertDontSee('storage_path')
            ->assertDontSee('Descargar')
            ->assertDontSee('Preview')
            ->assertDontSee('Visualizar');
    }

    public function test_authorized_user_can_search_certificados_by_main_fields(): void
    {
        $user = $this->createUser();
        $user->assignRole('admin');
        [$conversacion, $aviso] = $this->createConversationAndAviso();
        $anticipo = $this->createAnticipo($conversacion, $aviso, [
            'uuid' => '11111111-1111-1111-1111-111111111111',
            'numero_anticipo' => 'ANT-BUSCADO',
            'nombre_completo' => 'Ana Laboral',
            'legajo' => '12345',
            'wa_number' => '5491111111111',
        ]);
        $otroAnticipo = $this->createAnticipo($conversacion, $aviso, [
            'uuid' => '22222222-2222-2222-2222-222222222222',
            'numero_anticipo' => 'ANT-OTRO',
            'nombre_completo' => 'Luis Auditor',
            'legajo' => '67890',
            'wa_number' => '5492222222222',
        ]);

        Livewire::actingAs($user)
            ->test(ListAnticipoCertificados::class)
            ->searchTable('ANT-BUSCADO')
            ->assertCanSeeTableRecords([$anticipo])
            ->assertCanNotSeeTableRecords([$otroAnticipo]);

        Livewire::actingAs($user)
            ->test(ListAnticipoCertificados::class)
            ->searchTable('Luis Auditor')
            ->assertCanSeeTableRecords([$otroAnticipo])
            ->assertCanNotSeeTableRecords([$anticipo]);

        Livewire::actingAs($user)
            ->test(ListAnticipoCertificados::class)
            ->searchTable('12345')
            ->assertCanSeeTableRecords([$anticipo])
            ->assertCanNotSeeTableRecords([$otroAnticipo]);

        Livewire::actingAs($user)
            ->test(ListAnticipoCertificados::class)
            ->searchTable('5492222222222')
            ->assertCanSeeTableRecords([$otroAnticipo])
            ->assertCanNotSeeTableRecords([$anticipo]);

        Livewire::actingAs($user)
            ->test(ListAnticipoCertificados::class)
            ->searchTable('11111111-1111-1111-1111-111111111111')
            ->assertCanSeeTableRecords([$anticipo])
            ->assertCanNotSeeTableRecords([$otroAnticipo]);
    }

    public function test_authorized_user_can_filter_certificados_by_basic_fields_and_dates(): void
    {
        $user = $this->createUser();
        $user->assignRole('admin');
        [$conversacion, $aviso] = $this->createConversationAndAviso();
        $anticipo = $this->createAnticipo($conversacion, $aviso, [
            'estado' => 'recibido',
            'tipo_certificado' => 'medico',
            'sede' => 'Lujan',
            'registrado_en' => now()->setDate(2026, 4, 10)->setTime(9, 0),
            'created_at' => now()->setDate(2026, 4, 10)->setTime(9, 1),
        ]);
        $otroAnticipo = $this->createAnticipo($conversacion, $aviso, [
            'estado' => 'observado',
            'tipo_certificado' => 'reposo',
            'sede' => 'San Miguel',
            'registrado_en' => now()->setDate(2026, 3, 5)->setTime(9, 0),
            'created_at' => now()->setDate(2026, 3, 5)->setTime(9, 1),
        ]);

        Livewire::actingAs($user)
            ->test(ListAnticipoCertificados::class)
            ->assertTableFilterExists('estado')
            ->assertTableFilterExists('tipo_certificado')
            ->assertTableFilterExists('sede')
            ->assertTableFilterExists('registrado_en')
            ->assertTableFilterExists('created_at')
            ->filterTable('estado', 'recibido')
            ->assertCanSeeTableRecords([$anticipo])
            ->assertCanNotSeeTableRecords([$otroAnticipo]);

        Livewire::actingAs($user)
            ->test(ListAnticipoCertificados::class)
            ->filterTable('tipo_certificado', 'reposo')
            ->assertCanSeeTableRecords([$otroAnticipo])
            ->assertCanNotSeeTableRecords([$anticipo]);

        Livewire::actingAs($user)
            ->test(ListAnticipoCertificados::class)
            ->filterTable('sede', 'Lujan')
            ->assertCanSeeTableRecords([$anticipo])
            ->assertCanNotSeeTableRecords([$otroAnticipo]);

        Livewire::actingAs($user)
            ->test(ListAnticipoCertificados::class)
            ->filterTable('registrado_en', ['desde' => '2026-04-01', 'hasta' => '2026-04-30'])
            ->assertCanSeeTableRecords([$anticipo])
            ->assertCanNotSeeTableRecords([$otroAnticipo]);

        Livewire::actingAs($user)
            ->test(ListAnticipoCertificados::class)
            ->filterTable('created_at', ['desde' => '2026-03-01', 'hasta' => '2026-03-31'])
            ->assertCanSeeTableRecords([$otroAnticipo])
            ->assertCanNotSeeTableRecords([$anticipo]);
    }

    public function test_certificados_resource_is_read_only(): void
    {
        $user = $this->createUser();
        $user->assignRole('admin');
        [$conversacion, $aviso] = $this->createConversationAndAviso();
        $anticipo = $this->createAnticipo($conversacion, $aviso);

        $this->actingAs($user);

        $this->assertTrue(AnticipoCertificadoResource::canViewAny());
        $this->assertTrue(AnticipoCertificadoResource::canView($anticipo));
        $this->assertFalse(AnticipoCertificadoResource::canCreate());
        $this->assertFalse(AnticipoCertificadoResource::canEdit($anticipo));
        $this->assertFalse(AnticipoCertificadoResource::canDelete($anticipo));
        $this->assertFalse(AnticipoCertificadoResource::canDeleteAny());
    }

    public static function rolesWithCertificadosAccessProvider(): array
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

    /**
     * @return array{0: Conversacion, 1: Aviso}
     */
    private function createConversationAndAviso(): array
    {
        $conversacion = Conversacion::create([
            'uuid' => Str::uuid()->toString(),
            'wa_number' => '5491112345678',
            'canal' => Conversacion::CANAL_WHATSAPP,
            'tipo_flujo' => 'anticipo_certificado',
            'estado_actual' => 'iniciada',
            'paso_actual' => 'menu_principal',
            'activa' => true,
            'dni' => '30123456',
        ]);

        $aviso = Aviso::create([
            'conversacion_id' => $conversacion->id,
            'dni' => '30123456',
            'nombre_completo' => 'Persona de Prueba',
            'legajo' => '12345',
            'sede' => 'Lujan',
            'jornada_laboral' => 'Completa',
            'tipo' => 'ausencia',
            'estado' => 'inicial',
            'tipo_ausentismo' => 'enfermedad',
            'fecha_inicio' => now()->toDateString(),
            'fecha_fin' => now()->addDay()->toDateString(),
            'cantidad_dias' => 2,
            'wa_number' => '5491112345678',
        ]);

        return [$conversacion, $aviso];
    }

    private function createAnticipo(
        Conversacion $conversacion,
        Aviso $aviso,
        array $attributes = [],
    ): AnticipoCertificado {
        $createdAt = $attributes['created_at'] ?? null;
        $updatedAt = $attributes['updated_at'] ?? $createdAt;

        $anticipo = AnticipoCertificado::create([
            'uuid' => $attributes['uuid'] ?? Str::uuid()->toString(),
            'numero_anticipo' => $attributes['numero_anticipo'] ?? 'ANT-' . Str::random(8),
            'conversacion_id' => $conversacion->id,
            'aviso_id' => $aviso->id,
            'wa_number' => $attributes['wa_number'] ?? $aviso->wa_number,
            'nombre_completo' => $attributes['nombre_completo'] ?? $aviso->nombre_completo,
            'legajo' => $attributes['legajo'] ?? $aviso->legajo,
            'sede' => $attributes['sede'] ?? $aviso->sede,
            'jornada_laboral' => $attributes['jornada_laboral'] ?? $aviso->jornada_laboral,
            'tipo_certificado' => $attributes['tipo_certificado'] ?? 'medico',
            'estado' => $attributes['estado'] ?? 'inicial',
            'observaciones' => $attributes['observaciones'] ?? null,
            'registrado_en' => $attributes['registrado_en'] ?? now(),
            'metadata' => $attributes['metadata'] ?? null,
        ]);

        if ($createdAt) {
            $anticipo->created_at = $createdAt;
            $anticipo->updated_at = $updatedAt;
            $anticipo->save();
        }

        return $anticipo;
    }

    private function createArchivo(
        Conversacion $conversacion,
        AnticipoCertificado $anticipo,
        array $attributes = [],
    ): AnticipoCertificadoArchivo {
        return AnticipoCertificadoArchivo::create([
            'uuid' => $attributes['uuid'] ?? Str::uuid()->toString(),
            'anticipo_certificado_id' => $anticipo->id,
            'conversacion_id' => $conversacion->id,
            'provider_file_id' => $attributes['provider_file_id'] ?? 'provider-file-id',
            'nombre_original' => $attributes['nombre_original'] ?? 'certificado.pdf',
            'mime_type' => $attributes['mime_type'] ?? 'application/pdf',
            'extension' => $attributes['extension'] ?? 'pdf',
            'size_bytes' => $attributes['size_bytes'] ?? 1024,
            'storage_disk' => $attributes['storage_disk'] ?? 'local',
            'storage_path' => $attributes['storage_path'] ?? 'certificados/privado/certificado.pdf',
            'hash_archivo' => $attributes['hash_archivo'] ?? hash('sha256', Str::uuid()->toString()),
            'estado_validacion' => $attributes['estado_validacion'] ?? 'aceptado',
            'motivo_rechazo' => $attributes['motivo_rechazo'] ?? null,
            'metadata' => $attributes['metadata'] ?? null,
        ]);
    }
}
