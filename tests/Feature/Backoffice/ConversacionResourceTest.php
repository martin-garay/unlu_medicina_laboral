<?php

namespace Tests\Feature\Backoffice;

use App\Filament\Resources\ConversacionResource;
use App\Filament\Resources\ConversacionResource\Pages\ListConversaciones;
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

class ConversacionResourceTest extends TestCase
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

    public function test_user_without_backoffice_access_cannot_access_conversaciones_resource(): void
    {
        $user = $this->createUser();

        $this->actingAs($user)
            ->get(ConversacionResource::getUrl('index'))
            ->assertForbidden();
    }

    public function test_user_with_backoffice_access_but_without_conversaciones_permission_cannot_access_resource(): void
    {
        $user = $this->createUser();
        $user->givePermissionTo(Permission::findByName('backoffice.access'));

        $this->actingAs($user)
            ->get(ConversacionResource::getUrl('index'))
            ->assertForbidden();
    }

    #[DataProvider('rolesWithConversationAccessProvider')]
    public function test_roles_with_conversaciones_permission_can_access_resource(string $roleName): void
    {
        $user = $this->createUser();
        $user->assignRole($roleName);

        $this->actingAs($user)
            ->get(ConversacionResource::getUrl('index'))
            ->assertOk();
    }

    public function test_authorized_user_can_see_conversation_records_on_list_page(): void
    {
        $user = $this->createUser();
        $user->assignRole('admin');
        $conversacion = $this->createConversacion([
            'wa_number' => '5491111111111',
            'dni' => '30111222',
            'tipo_flujo' => 'aviso_ausencia',
            'estado_actual' => 'esperando_confirmacion',
            'paso_actual' => 'aviso.confirmacion_final',
        ]);
        $otraConversacion = $this->createConversacion([
            'wa_number' => '5492222222222',
            'dni' => '30222333',
            'tipo_flujo' => 'anticipo_certificado',
            'estado_actual' => 'finalizada',
            'paso_actual' => 'certificado.confirmacion_final',
            'activa' => false,
        ]);

        $this->actingAs($user);

        Livewire::test(ListConversaciones::class)
            ->assertCanSeeTableRecords([$conversacion, $otraConversacion])
            ->assertCountTableRecords(2)
            ->assertSee('5491111111111')
            ->assertSee('30111222')
            ->assertSee('aviso_ausencia')
            ->assertSee('esperando_confirmacion')
            ->assertDontSee('payload_crudo')
            ->assertDontSee('metadata');
    }

    public function test_conversaciones_resource_is_read_only(): void
    {
        $user = $this->createUser();
        $user->assignRole('admin');
        $conversacion = $this->createConversacion();

        $this->actingAs($user);

        $this->assertTrue(ConversacionResource::canViewAny());
        $this->assertTrue(ConversacionResource::canView($conversacion));
        $this->assertFalse(ConversacionResource::canCreate());
        $this->assertFalse(ConversacionResource::canEdit($conversacion));
        $this->assertFalse(ConversacionResource::canDelete($conversacion));
        $this->assertFalse(ConversacionResource::canDeleteAny());
    }

    public static function rolesWithConversationAccessProvider(): array
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

    private function createConversacion(array $attributes = []): Conversacion
    {
        return Conversacion::create([
            'uuid' => $attributes['uuid'] ?? Str::uuid()->toString(),
            'wa_number' => $attributes['wa_number'] ?? '5491112345678',
            'canal' => $attributes['canal'] ?? Conversacion::CANAL_WHATSAPP,
            'tipo_flujo' => $attributes['tipo_flujo'] ?? 'aviso_ausencia',
            'estado_actual' => $attributes['estado_actual'] ?? 'iniciada',
            'paso_actual' => $attributes['paso_actual'] ?? 'menu_principal',
            'activa' => $attributes['activa'] ?? true,
            'cantidad_mensajes_recibidos' => $attributes['cantidad_mensajes_recibidos'] ?? 2,
            'cantidad_mensajes_enviados' => $attributes['cantidad_mensajes_enviados'] ?? 1,
            'cantidad_mensajes_validos' => $attributes['cantidad_mensajes_validos'] ?? 2,
            'cantidad_mensajes_invalidos' => $attributes['cantidad_mensajes_invalidos'] ?? 0,
            'cantidad_intentos_totales' => $attributes['cantidad_intentos_totales'] ?? 1,
            'ultimo_mensaje_recibido_en' => $attributes['ultimo_mensaje_recibido_en'] ?? now()->subMinutes(5),
            'ultimo_mensaje_enviado_en' => $attributes['ultimo_mensaje_enviado_en'] ?? now()->subMinutes(4),
            'finalizada_en' => $attributes['finalizada_en'] ?? null,
            'dni' => $attributes['dni'] ?? '30123456',
            'estado' => $attributes['estado'] ?? 'esperando_dni',
            'tipo' => $attributes['tipo'] ?? null,
            'metadata' => $attributes['metadata'] ?? ['ip' => '127.0.0.1'],
        ]);
    }
}
