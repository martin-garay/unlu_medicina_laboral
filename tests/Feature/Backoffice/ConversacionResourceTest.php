<?php

namespace Tests\Feature\Backoffice;

use App\Filament\Resources\ConversacionResource;
use App\Filament\Resources\ConversacionResource\Pages\ListConversaciones;
use App\Models\Conversacion;
use App\Models\ConversacionEvento;
use App\Models\ConversacionMensaje;
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

    public function test_authorized_user_can_search_conversations_by_main_fields(): void
    {
        $user = $this->createUser();
        $user->assignRole('admin');
        $conversacion = $this->createConversacion([
            'uuid' => '11111111-1111-1111-1111-111111111111',
            'wa_number' => '549116667777',
            'dni' => '31111222',
        ]);
        $otraConversacion = $this->createConversacion([
            'uuid' => '22222222-2222-2222-2222-222222222222',
            'wa_number' => '549118889999',
            'dni' => '32222333',
        ]);

        Livewire::actingAs($user)
            ->test(ListConversaciones::class)
            ->searchTable('31111222')
            ->assertCanSeeTableRecords([$conversacion])
            ->assertCanNotSeeTableRecords([$otraConversacion]);

        Livewire::actingAs($user)
            ->test(ListConversaciones::class)
            ->searchTable('549118889999')
            ->assertCanSeeTableRecords([$otraConversacion])
            ->assertCanNotSeeTableRecords([$conversacion]);

        Livewire::actingAs($user)
            ->test(ListConversaciones::class)
            ->searchTable('11111111-1111-1111-1111-111111111111')
            ->assertCanSeeTableRecords([$conversacion])
            ->assertCanNotSeeTableRecords([$otraConversacion]);
    }

    public function test_authorized_user_can_filter_conversations_by_operational_fields(): void
    {
        $user = $this->createUser();
        $user->assignRole('admin');
        $conversacion = $this->createConversacion([
            'canal' => Conversacion::CANAL_WHATSAPP,
            'tipo_flujo' => 'aviso_ausencia',
            'estado_actual' => 'en_progreso',
            'activa' => true,
            'ultimo_mensaje_recibido_en' => now()->setDate(2026, 4, 10)->setTime(9, 0),
            'finalizada_en' => null,
            'created_at' => now()->setDate(2026, 4, 10)->setTime(9, 1),
        ]);
        $otraConversacion = $this->createConversacion([
            'canal' => Conversacion::CANAL_INTERNO,
            'tipo_flujo' => 'anticipo_certificado',
            'estado_actual' => 'finalizada',
            'activa' => false,
            'ultimo_mensaje_recibido_en' => now()->setDate(2026, 3, 5)->setTime(9, 0),
            'finalizada_en' => now()->setDate(2026, 3, 5)->setTime(9, 30),
            'created_at' => now()->setDate(2026, 3, 5)->setTime(9, 1),
        ]);

        Livewire::actingAs($user)
            ->test(ListConversaciones::class)
            ->assertTableFilterExists('canal')
            ->assertTableFilterExists('tipo_flujo')
            ->assertTableFilterExists('estado_actual')
            ->assertTableFilterExists('activa')
            ->assertTableFilterExists('ultimo_mensaje_recibido_en')
            ->assertTableFilterExists('finalizada_en')
            ->assertTableFilterExists('created_at')
            ->filterTable('canal', Conversacion::CANAL_WHATSAPP)
            ->assertCanSeeTableRecords([$conversacion])
            ->assertCanNotSeeTableRecords([$otraConversacion]);

        Livewire::actingAs($user)
            ->test(ListConversaciones::class)
            ->filterTable('tipo_flujo', 'anticipo_certificado')
            ->assertCanSeeTableRecords([$otraConversacion])
            ->assertCanNotSeeTableRecords([$conversacion]);

        Livewire::actingAs($user)
            ->test(ListConversaciones::class)
            ->filterTable('estado_actual', 'en_progreso')
            ->assertCanSeeTableRecords([$conversacion])
            ->assertCanNotSeeTableRecords([$otraConversacion]);

        Livewire::actingAs($user)
            ->test(ListConversaciones::class)
            ->filterTable('activa', true)
            ->assertCanSeeTableRecords([$conversacion])
            ->assertCanNotSeeTableRecords([$otraConversacion]);

        Livewire::actingAs($user)
            ->test(ListConversaciones::class)
            ->filterTable('ultimo_mensaje_recibido_en', ['desde' => '2026-04-01', 'hasta' => '2026-04-30'])
            ->assertCanSeeTableRecords([$conversacion])
            ->assertCanNotSeeTableRecords([$otraConversacion]);

        Livewire::actingAs($user)
            ->test(ListConversaciones::class)
            ->filterTable('finalizada_en', ['desde' => '2026-03-01', 'hasta' => '2026-03-31'])
            ->assertCanSeeTableRecords([$otraConversacion])
            ->assertCanNotSeeTableRecords([$conversacion]);

        Livewire::actingAs($user)
            ->test(ListConversaciones::class)
            ->filterTable('created_at', ['desde' => '2026-04-01', 'hasta' => '2026-04-30'])
            ->assertCanSeeTableRecords([$conversacion])
            ->assertCanNotSeeTableRecords([$otraConversacion]);
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

    public function test_user_with_list_permission_but_without_history_permission_does_not_see_view_action(): void
    {
        $user = $this->createUser();
        $user->givePermissionTo('backoffice.access', 'conversaciones.view');
        $conversacion = $this->createConversacion();

        $this->actingAs($user)
            ->get(ConversacionResource::getUrl('index'))
            ->assertOk();

        Livewire::actingAs($user)
            ->test(ListConversaciones::class)
            ->assertTableActionHidden('view', $conversacion);
    }

    public function test_user_without_history_permission_cannot_access_history_by_direct_url(): void
    {
        $user = $this->createUser();
        $user->givePermissionTo('backoffice.access', 'conversaciones.view');
        $conversacion = $this->createConversacion();

        $this->actingAs($user)
            ->get(ConversacionResource::getUrl('view', ['record' => $conversacion]))
            ->assertForbidden();
    }

    public function test_user_with_history_permission_sees_eye_action_and_can_access_history(): void
    {
        $user = $this->createUser();
        $user->assignRole('admin');
        $conversacion = $this->createConversacion();

        Livewire::actingAs($user)
            ->test(ListConversaciones::class)
            ->assertTableActionVisible('view', $conversacion)
            ->assertTableActionHasIcon('view', 'heroicon-o-eye', $conversacion)
            ->assertTableActionHasLabel('view', 'Ver historial', $conversacion)
            ->assertTableActionHasUrl(
                'view',
                ConversacionResource::getUrl('view', ['record' => $conversacion]),
                $conversacion,
            );

        $this->actingAs($user)
            ->get(ConversacionResource::getUrl('view', ['record' => $conversacion]))
            ->assertOk()
            ->assertSee('Volver');
    }

    public function test_history_page_shows_ordered_message_thread_and_events_without_sensitive_payloads(): void
    {
        $user = $this->createUser();
        $user->assignRole('admin');
        $conversacion = $this->createConversacion([
            'wa_number' => '549113334444',
            'dni' => '30999888',
            'estado_actual' => 'en_progreso',
            'paso_actual' => 'aviso.fecha_desde',
            'metadata' => ['secret' => 'NO_MOSTRAR_METADATA_CONVERSACION'],
        ]);

        $this->createMensaje($conversacion, [
            'direccion' => ConversacionMensaje::DIRECCION_SALIENTE,
            'contenido_texto' => 'Segundo mensaje chatbot',
            'tipo_mensaje' => 'text',
            'step_key' => 'menu_principal',
            'message_key' => 'mensaje.segundo',
            'template_name' => 'template_segundo',
            'payload_crudo' => ['secret' => 'NO_MOSTRAR_PAYLOAD_SEGUNDO'],
            'metadata' => ['secret' => 'NO_MOSTRAR_METADATA_SEGUNDO'],
            'created_at' => now()->setTime(10, 1),
        ]);
        $this->createMensaje($conversacion, [
            'direccion' => ConversacionMensaje::DIRECCION_ENTRANTE,
            'contenido_texto' => 'Primer mensaje usuario',
            'tipo_mensaje' => 'text',
            'step_key' => 'inicio',
            'payload_crudo' => ['secret' => 'NO_MOSTRAR_PAYLOAD_PRIMERO'],
            'metadata' => ['secret' => 'NO_MOSTRAR_METADATA_PRIMERO'],
            'created_at' => now()->setTime(10, 0),
        ]);

        $this->createEvento($conversacion, [
            'tipo_evento' => 'evento_segundo',
            'descripcion' => 'Segundo evento registrado',
            'codigo' => 'SEGUNDO',
            'created_at' => now()->setTime(10, 3),
        ]);
        $this->createEvento($conversacion, [
            'tipo_evento' => 'evento_primero',
            'descripcion' => 'Primer evento registrado',
            'codigo' => 'PRIMERO',
            'created_at' => now()->setTime(10, 2),
        ]);

        $this->actingAs($user)
            ->get(ConversacionResource::getUrl('view', ['record' => $conversacion]))
            ->assertOk()
            ->assertSee('Volver')
            ->assertSee('549113334444')
            ->assertSee('30999888')
            ->assertSee('en_progreso')
            ->assertSee('Usuario')
            ->assertSee('Chatbot')
            ->assertSeeInOrder(['Primer mensaje usuario', 'Segundo mensaje chatbot'])
            ->assertSeeInOrder(['evento_primero', 'evento_segundo'])
            ->assertSeeInOrder(['Primer evento registrado', 'Segundo evento registrado'])
            ->assertDontSee('NO_MOSTRAR_PAYLOAD_PRIMERO')
            ->assertDontSee('NO_MOSTRAR_PAYLOAD_SEGUNDO')
            ->assertDontSee('NO_MOSTRAR_METADATA_PRIMERO')
            ->assertDontSee('NO_MOSTRAR_METADATA_SEGUNDO')
            ->assertDontSee('NO_MOSTRAR_METADATA_CONVERSACION')
            ->assertDontSee('payload_crudo')
            ->assertDontSee('Reprocesar')
            ->assertDontSee('Responder')
            ->assertDontSee('Reenviar');
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
        $createdAt = $attributes['created_at'] ?? null;
        $updatedAt = $attributes['updated_at'] ?? $createdAt;

        $conversacion = Conversacion::create([
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

        if ($createdAt) {
            $conversacion->created_at = $createdAt;
            $conversacion->updated_at = $updatedAt;
            $conversacion->save();
        }

        return $conversacion;
    }

    private function createMensaje(Conversacion $conversacion, array $attributes = []): ConversacionMensaje
    {
        $createdAt = $attributes['created_at'] ?? now();

        $mensaje = new ConversacionMensaje([
            'uuid' => $attributes['uuid'] ?? Str::uuid()->toString(),
            'conversacion_id' => $conversacion->id,
            'direccion' => $attributes['direccion'] ?? ConversacionMensaje::DIRECCION_ENTRANTE,
            'provider_message_id' => $attributes['provider_message_id'] ?? null,
            'tipo_mensaje' => $attributes['tipo_mensaje'] ?? 'text',
            'step_key' => $attributes['step_key'] ?? 'inicio',
            'contenido_texto' => $attributes['contenido_texto'] ?? 'Mensaje de prueba',
            'es_valido' => $attributes['es_valido'] ?? true,
            'motivo_invalidez' => $attributes['motivo_invalidez'] ?? null,
            'message_key' => $attributes['message_key'] ?? null,
            'template_name' => $attributes['template_name'] ?? null,
            'payload_crudo' => $attributes['payload_crudo'] ?? null,
            'metadata' => $attributes['metadata'] ?? null,
        ]);
        $mensaje->created_at = $createdAt;
        $mensaje->updated_at = $createdAt;
        $mensaje->save();

        return $mensaje;
    }

    private function createEvento(Conversacion $conversacion, array $attributes = []): ConversacionEvento
    {
        $createdAt = $attributes['created_at'] ?? now();

        $evento = new ConversacionEvento([
            'uuid' => $attributes['uuid'] ?? Str::uuid()->toString(),
            'conversacion_id' => $conversacion->id,
            'tipo_evento' => $attributes['tipo_evento'] ?? 'evento_prueba',
            'step_key' => $attributes['step_key'] ?? 'inicio',
            'descripcion' => $attributes['descripcion'] ?? 'Evento de prueba',
            'codigo' => $attributes['codigo'] ?? null,
            'metadata' => $attributes['metadata'] ?? null,
        ]);
        $evento->created_at = $createdAt;
        $evento->updated_at = $createdAt;
        $evento->save();

        return $evento;
    }
}
