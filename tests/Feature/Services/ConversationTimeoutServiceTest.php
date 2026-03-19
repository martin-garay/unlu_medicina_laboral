<?php

namespace Tests\Feature\Services;

use App\Models\Conversacion;
use App\Services\ConversationManager;
use App\Services\ConversationTimeoutService;
use App\Services\WhatsAppSender;
use Carbon\Carbon;
use Tests\Concerns\CreatesTestingSchema;
use Tests\TestCase;

class ConversationTimeoutServiceTest extends TestCase
{
    use CreatesTestingSchema;

    protected function setUp(): void
    {
        parent::setUp();
        $this->createTestingSchema();

        $this->app->instance(WhatsAppSender::class, new class extends WhatsAppSender
        {
            public array $sent = [];

            public function __construct()
            {
                parent::__construct(null, null);
            }

            public function sendText(string $to, string $message): void
            {
                $this->sent[] = [
                    'to' => $to,
                    'message' => $message,
                ];
            }
        });
    }

    public function test_process_sends_first_warning_once_and_marks_threshold(): void
    {
        config()->set('medicina_laboral.conversation.first_inactivity_minutes', 30);
        config()->set('medicina_laboral.conversation.second_inactivity_minutes', 60);

        $conversation = $this->createInactiveConversation([
            'ultimo_mensaje_recibido_en' => Carbon::parse('2026-03-19 09:20:00'),
        ]);

        $summary = app(ConversationTimeoutService::class)->process(Carbon::parse('2026-03-19 10:00:00'));

        $conversation = $conversation->fresh();

        $this->assertSame(1, $summary['warning_1_sent']);
        $this->assertNotNull($conversation->primer_umbral_notificado_en);
        $this->assertTrue($conversation->activa);
        $this->assertDatabaseHas('conversacion_eventos', [
            'conversacion_id' => $conversation->id,
            'tipo_evento' => 'timeout_warning_1',
            'codigo' => 'timeout_warning_1',
        ]);
        $this->assertDatabaseHas('conversacion_mensajes', [
            'conversacion_id' => $conversation->id,
            'direccion' => 'out',
            'message_key' => 'whatsapp.timeouts.recordatorio',
        ]);
    }

    public function test_process_does_not_repeat_first_warning_when_it_was_already_sent(): void
    {
        config()->set('medicina_laboral.conversation.first_inactivity_minutes', 30);
        config()->set('medicina_laboral.conversation.second_inactivity_minutes', 60);

        $conversation = $this->createInactiveConversation([
            'ultimo_mensaje_recibido_en' => Carbon::parse('2026-03-19 09:20:00'),
            'primer_umbral_notificado_en' => Carbon::parse('2026-03-19 09:55:00'),
        ]);

        $summary = app(ConversationTimeoutService::class)->process(Carbon::parse('2026-03-19 10:00:00'));

        $this->assertSame(0, $summary['warning_1_sent']);
        $this->assertDatabaseCount('conversacion_mensajes', 0);
    }

    public function test_process_cancels_conversation_when_second_threshold_is_reached(): void
    {
        config()->set('medicina_laboral.conversation.first_inactivity_minutes', 30);
        config()->set('medicina_laboral.conversation.second_inactivity_minutes', 60);

        $conversation = $this->createInactiveConversation([
            'ultimo_mensaje_recibido_en' => Carbon::parse('2026-03-19 08:50:00'),
            'primer_umbral_notificado_en' => Carbon::parse('2026-03-19 09:30:00'),
        ]);

        $summary = app(ConversationTimeoutService::class)->process(Carbon::parse('2026-03-19 10:00:00'));

        $conversation = $conversation->fresh();

        $this->assertSame(1, $summary['cancelled']);
        $this->assertFalse($conversation->activa);
        $this->assertSame('inactivity_timeout', $conversation->motivo_finalizacion);
        $this->assertNotNull($conversation->segundo_umbral_notificado_en);
        $this->assertDatabaseHas('conversacion_eventos', [
            'conversacion_id' => $conversation->id,
            'tipo_evento' => 'timeout_warning_2',
            'codigo' => 'timeout_warning_2',
        ]);
        $this->assertDatabaseHas('conversacion_eventos', [
            'conversacion_id' => $conversation->id,
            'tipo_evento' => 'conversation_cancelled_by_inactivity',
            'codigo' => 'inactivity_timeout',
        ]);
        $this->assertDatabaseHas('conversacion_mensajes', [
            'conversacion_id' => $conversation->id,
            'direccion' => 'out',
            'message_key' => 'whatsapp.timeouts.cancelacion',
        ]);
    }

    private function createInactiveConversation(array $attributes = []): Conversacion
    {
        return app(ConversationManager::class)->createConversation('5491111111111', array_merge([
            'estado' => 'identificacion_legajo',
            'estado_actual' => 'identificacion_legajo',
            'paso_actual' => 'identificacion_legajo',
            'activa' => true,
            'created_at' => Carbon::parse('2026-03-19 08:00:00'),
            'updated_at' => Carbon::parse('2026-03-19 08:00:00'),
        ], $attributes));
    }
}
