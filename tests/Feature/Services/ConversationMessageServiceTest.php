<?php

namespace Tests\Feature\Services;

use App\Models\ConversacionMensaje;
use App\Services\ConversationManager;
use App\Services\ConversationMessageService;
use Tests\Concerns\CreatesTestingSchema;
use Tests\TestCase;

class ConversationMessageServiceTest extends TestCase
{
    use CreatesTestingSchema;

    protected function setUp(): void
    {
        parent::setUp();
        $this->createTestingSchema();
    }

    public function test_register_incoming_message_persists_message_and_updates_counters(): void
    {
        $conversation = app(ConversationManager::class)->createConversation('5491111111111');

        $message = app(ConversationMessageService::class)->registerIncomingMessage($conversation, [
            'tipo_mensaje' => 'text',
            'contenido_texto' => 'hola',
            'es_valido' => true,
            'incrementar_intentos' => 1,
        ]);

        $this->assertSame(ConversacionMensaje::DIRECCION_ENTRANTE, $message->direccion);
        $this->assertDatabaseHas('conversacion_mensajes', [
            'id' => $message->id,
            'direccion' => 'in',
            'contenido_texto' => 'hola',
        ]);
        $this->assertSame(1, $conversation->fresh()->cantidad_mensajes_recibidos);
        $this->assertSame(1, $conversation->fresh()->cantidad_mensajes_validos);
        $this->assertSame(1, $conversation->fresh()->cantidad_intentos_actual);
    }

    public function test_register_outgoing_message_persists_message_and_updates_sent_counter(): void
    {
        $conversation = app(ConversationManager::class)->createConversation('5491111111111');

        $message = app(ConversationMessageService::class)->registerOutgoingMessage($conversation, [
            'tipo_mensaje' => 'text',
            'contenido_texto' => 'respuesta',
        ]);

        $this->assertSame(ConversacionMensaje::DIRECCION_SALIENTE, $message->direccion);
        $this->assertDatabaseHas('conversacion_mensajes', [
            'id' => $message->id,
            'direccion' => 'out',
            'contenido_texto' => 'respuesta',
        ]);
        $this->assertSame(1, $conversation->fresh()->cantidad_mensajes_enviados);
    }
}
