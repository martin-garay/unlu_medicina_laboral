<?php

namespace Tests\Feature\Services\Conversation;

use App\Models\Conversacion;
use App\Services\Conversation\ConversationInboundMessage;
use App\Services\Conversation\ConversationInteractionService;
use Illuminate\Support\Facades\Log;
use Tests\Concerns\CreatesTestingSchema;
use Tests\TestCase;

class ConversationInteractionServiceTest extends TestCase
{
    use CreatesTestingSchema;

    protected function setUp(): void
    {
        parent::setUp();
        $this->createTestingSchema();
        $compiledPath = sys_get_temp_dir() . '/unlu-medicina-tests-views';

        if (!is_dir($compiledPath)) {
            mkdir($compiledPath, 0777, true);
        }

        config()->set('view.compiled', $compiledPath);
    }

    public function test_first_inbound_message_creates_conversation_and_returns_text_and_menu_outputs(): void
    {
        $result = app(ConversationInteractionService::class)->handleInboundMessage(
            new ConversationInboundMessage(
                channel: Conversacion::CANAL_WHATSAPP,
                participantId: '5491111111111',
                text: 'hola',
                providerMessageId: 'wamid-1',
                incomingMessageType: 'text',
                rawPayload: ['text' => ['body' => 'hola']],
                content: 'hola',
            )
        );

        $this->assertSame('menu_principal', $result->conversation->currentStepKey());
        $this->assertCount(2, $result->outboundMessages);
        $this->assertTrue($result->outboundMessages[0]->isText());
        $this->assertTrue($result->outboundMessages[1]->isMenu());
        $this->assertDatabaseHas('conversaciones', [
            'wa_number' => '5491111111111',
            'paso_actual' => 'menu_principal',
        ]);
        $this->assertDatabaseHas('conversacion_eventos', [
            'conversacion_id' => $result->conversation->id,
            'tipo_evento' => 'conversation_started',
        ]);
        $this->assertDatabaseHas('conversacion_mensajes', [
            'conversacion_id' => $result->conversation->id,
            'direccion' => 'in',
            'contenido_texto' => 'hola',
        ]);
    }

    public function test_valid_menu_selection_returns_next_step_text_and_updates_conversation(): void
    {
        $result = app(ConversationInteractionService::class)->handleInboundMessage(
            new ConversationInboundMessage(
                channel: Conversacion::CANAL_WHATSAPP,
                participantId: '5492222222222',
                text: '2',
                providerMessageId: 'wamid-2',
                incomingMessageType: 'text',
                rawPayload: ['text' => ['body' => '2']],
                content: '2',
            )
        );

        $conversation = $result->conversation->fresh();

        $this->assertSame('identificacion_nombre', $conversation->currentStepKey());
        $this->assertSame('inasistencia', $conversation->tipo_flujo);
        $this->assertCount(1, $result->outboundMessages);
        $this->assertTrue($result->outboundMessages[0]->isText());
        $this->assertSame(__('whatsapp.identificacion.nombre_completo'), $result->outboundMessages[0]->text);
        $this->assertDatabaseHas('conversacion_eventos', [
            'conversacion_id' => $conversation->id,
            'tipo_evento' => 'menu_option_selected',
        ]);
    }

    public function test_channel_is_used_when_creating_and_finding_conversation(): void
    {
        $first = app(ConversationInteractionService::class)->handleInboundMessage(
            new ConversationInboundMessage(
                channel: 'internal_chat',
                participantId: 'dev-user-1',
                text: 'hola',
                providerMessageId: 'internal-1',
                incomingMessageType: 'text',
                rawPayload: ['text' => 'hola'],
                content: 'hola',
            )
        );

        $second = app(ConversationInteractionService::class)->handleInboundMessage(
            new ConversationInboundMessage(
                channel: 'internal_chat',
                participantId: 'dev-user-1',
                text: '2',
                providerMessageId: 'internal-2',
                incomingMessageType: 'text',
                rawPayload: ['text' => '2'],
                content: '2',
            )
        );

        $this->assertSame($first->conversation->id, $second->conversation->id);
        $this->assertSame('internal_chat', $second->conversation->fresh()->canal);
    }

    public function test_interaction_processing_writes_structured_log_context(): void
    {
        Log::spy();

        $result = app(ConversationInteractionService::class)->handleInboundMessage(
            new ConversationInboundMessage(
                channel: Conversacion::CANAL_INTERNO,
                participantId: 'dev-user-log-1',
                text: 'hola',
                providerMessageId: 'internal-log-1',
                incomingMessageType: 'text',
                rawPayload: ['text' => 'hola'],
                content: 'hola',
            )
        );

        Log::shouldHaveReceived('info')
            ->withArgs(function (string $message, array $context) use ($result): bool {
                return $message === 'Conversation interaction processed'
                    && $context['conversation_id'] === $result->conversation->id
                    && $context['channel'] === Conversacion::CANAL_INTERNO
                    && $context['participant_id'] === 'dev-user-log-1'
                    && $context['outbound_count'] === 2;
            })
            ->once();
    }
}
