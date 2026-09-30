<?php

namespace Tests\Feature\Http;

use App\Models\Aviso;
use App\Models\Conversacion;
use App\Services\AvisoService;
use App\Services\Conversation\ConversationInboundMessage;
use App\Services\Conversation\ConversationInteractionService;
use App\Services\ConversationManager;
use Illuminate\Support\Facades\Http;
use Illuminate\Testing\TestResponse;
use Illuminate\Validation\ValidationException;
use Tests\Concerns\CreatesChatCatalogSchema;
use Tests\Concerns\CreatesTestingSchema;
use Tests\TestCase;

class AvisoFamiliarFlowTest extends TestCase
{
    use CreatesChatCatalogSchema;
    use CreatesTestingSchema;

    protected function setUp(): void
    {
        parent::setUp();
        $this->createTestingSchema();
        $this->createChatCatalogSchema();
        config()->set('medicina_laboral.mail.driver', 'null');
        Http::fake();
    }

    private function send(string $text, ?string $step = null): TestResponse
    {
        $response = $this->postJson('/api/internal/chat/messages', [
            'participant_id' => 'family-test', 'text' => $text,
        ])->assertOk();
        if ($step !== null) {
            $response->assertJsonPath('conversation.current_step', $step);
        }

        return $response;
    }

    private function startFlow(string $type = '2'): void
    {
        foreach ([
            ['hola', 'menu_principal'], ['2', 'identificacion_nombre'],
            ['Ana Pérez', 'identificacion_legajo'], ['10001', 'identificacion_sede'],
            ['1', 'identificacion_jornada'], ['1', 'aviso_fecha_desde'],
            ['24/09/2026', 'aviso_fecha_hasta'], ['25/09/2026', 'aviso_tipo_ausentismo'],
            [$type, 'aviso_motivo'], ['Acompañamiento por fiebre', 'aviso_domicilio_circunstancial'],
            ['2', 'aviso_observaciones'],
        ] as [$text, $step]) {
            $this->send($text, $step);
        }
    }

    public function test_complete_family_flow_persists_required_data_and_closes_conversation(): void
    {
        $this->startFlow();
        $this->send('sin observaciones', 'aviso_nombre_familiar');
        $this->send('  María Pérez  ', 'aviso_parentesco')
            ->assertJsonCount(5, 'outbound_messages.0.menu.buttons');
        $response = $this->send('3', 'aviso_confirmacion_final');
        $this->assertStringContainsString('María Pérez', $response->json('outbound_messages.0.text'));
        $this->assertStringContainsString('Hijo/a', $response->json('outbound_messages.0.text'));
        $this->assertDatabaseCount('avisos', 0);
        $this->send('1', 'aviso_registrado')->assertJsonPath('conversation.active', false);
        $this->assertDatabaseCount('avisos', 1);
        $aviso = Aviso::firstOrFail();
        $this->assertSame('María Pérez', $aviso->metadata['aviso']['nombre_familiar']);
        $this->assertSame('hijo_hija', $aviso->metadata['aviso']['parentesco']);
        $this->assertSame($aviso->id, Conversacion::firstOrFail()->aviso_id);
        $this->assertDatabaseHas('conversacion_mensajes', [
            'conversacion_id' => $aviso->conversacion_id, 'step_key' => 'aviso_nombre_familiar',
            'direccion' => 'in', 'es_valido' => true,
        ]);
        $this->assertTrue(Conversacion::firstOrFail()->eventos()->exists());
        $this->send('1', 'menu_principal');
        $this->assertDatabaseCount('avisos', 1);
        $this->assertDatabaseCount('conversaciones', 2);
    }

    public function test_standard_flow_still_finishes_without_family_data(): void
    {
        $this->startFlow('1');
        $this->send('no', 'aviso_confirmacion_final');
        $this->send('1', 'aviso_registrado');
        $this->assertDatabaseCount('avisos', 1);
        $this->assertSame('por_enfermedad', Aviso::firstOrFail()->tipo_ausentismo);
    }

    public function test_invalid_inputs_retry_and_cancellation_preserves_history(): void
    {
        $this->startFlow();
        $this->send('no', 'aviso_nombre_familiar');
        $this->send('   ', 'aviso_nombre_familiar');
        $this->assertSame(1, Conversacion::firstOrFail()->cantidad_intentos_actual);
        $this->send('María Pérez', 'aviso_parentesco');
        $this->send('desconocido', 'aviso_parentesco');
        $this->assertSame(1, Conversacion::firstOrFail()->cantidad_intentos_actual);
        $count = Conversacion::firstOrFail()->mensajes()->count();
        $this->send('cancelar', 'menu_principal');
        $this->assertGreaterThan($count, Conversacion::firstOrFail()->mensajes()->count());
        $this->assertDatabaseCount('avisos', 0);
    }

    private function conversationAt(string $step, array $family = [], string $channel = Conversacion::CANAL_INTERNO): Conversacion
    {
        return app(ConversationManager::class)->createConversationForChannel($channel, 'family-test', [
            'estado' => $step, 'estado_actual' => $step, 'paso_actual' => $step,
            'tipo_flujo' => 'inasistencia',
            'metadata' => ['aviso' => array_merge([
                'tipo_ausentismo' => 'atencion_familiar_enfermo',
                'fecha_desde' => '2026-09-24', 'fecha_hasta' => '2026-09-25',
            ], $family)],
        ]);
    }

    public function test_old_placeholder_prompts_for_name_without_consuming_message(): void
    {
        $conversation = $this->conversationAt('aviso_familiar_pendiente');
        $this->send('continuar', 'aviso_nombre_familiar');
        $this->assertArrayNotHasKey('nombre_familiar', $conversation->fresh()->metadata['aviso']);
        $this->send('María Pérez', 'aviso_parentesco');
        $this->send('Madre', 'aviso_confirmacion_final');
        $this->send('1', 'aviso_registrado');
    }

    public function test_confirmation_cannot_bypass_required_family_data(): void
    {
        $this->conversationAt('aviso_confirmacion_final');
        $this->send('1', 'aviso_nombre_familiar');
        $this->assertDatabaseCount('avisos', 0);
    }

    public function test_materialization_rejects_missing_name_or_invalid_relationship(): void
    {
        $conversation = $this->conversationAt('aviso_confirmacion_final');
        foreach ([[], ['nombre_familiar' => 'María Pérez'], ['nombre_familiar' => 'María Pérez', 'parentesco' => 'invalido']] as $family) {
            $conversation->metadata = ['aviso' => array_merge(['tipo_ausentismo' => 'atencion_familiar_enfermo'], $family)];
            try {
                app(AvisoService::class)->createFromConversation($conversation);
                $this->fail('Incomplete family data must not create an aviso.');
            } catch (ValidationException $e) {
                $this->assertArrayHasKey('familiar', $e->errors());
            }
        }
        $this->assertDatabaseCount('avisos', 0);
    }

    public function test_document_cannot_be_used_as_family_name(): void
    {
        $conversation = $this->conversationAt('aviso_nombre_familiar');
        $result = app(ConversationInteractionService::class)->handleInboundMessage(new ConversationInboundMessage(
            channel: Conversacion::CANAL_INTERNO, participantId: 'family-test',
            text: 'María Pérez', incomingMessageType: 'document',
        ));
        $this->assertSame('aviso_nombre_familiar', $result->conversation->currentStepKey());
        $this->assertSame(1, $conversation->fresh()->cantidad_intentos_actual);
    }

    public function test_whatsapp_list_reply_is_normalized_and_duplicate_confirmation_is_ignored(): void
    {
        config()->set('medicina_laboral.whatsapp.token', 'test-token');
        config()->set('medicina_laboral.whatsapp.phone_id', 'test-phone');
        $conversation = $this->conversationAt('aviso_nombre_familiar', [], Conversacion::CANAL_WHATSAPP);
        $send = function (array $message): void {
            $this->postJson('/api/whatsapp/webhook', ['entry' => [['changes' => [['value' => [
                'messages' => [array_merge(['from' => 'family-test'], $message)],
            ]]]]]])->assertOk();
        };
        $send(['id' => 'name-1', 'text' => ['body' => 'María Pérez']]);
        Http::assertSent(fn ($request) => $request['interactive']['type'] === 'list'
            && count($request['interactive']['action']['sections'][0]['rows']) === 5);
        $send(['id' => 'relationship-1', 'interactive' => ['list_reply' => ['id' => 'parentesco_madre', 'title' => 'Madre']]]);
        $this->assertSame('aviso_confirmacion_final', $conversation->fresh()->currentStepKey());
        $send(['id' => 'confirm-1', 'text' => ['body' => '1']]);
        $send(['id' => 'confirm-1', 'text' => ['body' => '1']]);
        $this->assertDatabaseCount('avisos', 1);
        $this->assertSame('madre', Aviso::firstOrFail()->metadata['aviso']['parentesco']);
        $this->assertDatabaseHas('conversacion_mensajes', [
            'conversacion_id' => $conversation->id, 'direccion' => 'out', 'step_key' => 'aviso_parentesco',
        ]);
    }
}
