<?php

namespace Tests\Feature\Services;

use App\Services\ConversationEventService;
use App\Services\ConversationManager;
use Tests\Concerns\CreatesTestingSchema;
use Tests\TestCase;

class ConversationEventServiceTest extends TestCase
{
    use CreatesTestingSchema;

    protected function setUp(): void
    {
        parent::setUp();
        $this->createTestingSchema();
    }

    public function test_record_state_change_persists_expected_event_data(): void
    {
        $conversation = app(ConversationManager::class)->createConversation('5491111111111');

        $event = app(ConversationEventService::class)->recordStateChange($conversation, 'menu_principal', 'identificacion_nombre');

        $this->assertDatabaseHas('conversacion_eventos', [
            'id' => $event->id,
            'tipo_evento' => 'state_changed',
            'codigo' => 'state_changed',
        ]);
        $this->assertSame('menu_principal', $event->metadata['from_state']);
        $this->assertSame('identificacion_nombre', $event->metadata['to_state']);
    }

    public function test_record_conversation_closed_persists_reason_in_codigo(): void
    {
        $conversation = app(ConversationManager::class)->createConversation('5491111111111');

        $event = app(ConversationEventService::class)->recordConversationClosed($conversation, 'completed');

        $this->assertDatabaseHas('conversacion_eventos', [
            'id' => $event->id,
            'tipo_evento' => 'conversation_closed',
            'codigo' => 'completed',
        ]);
    }
}
