<?php

namespace Tests\Feature\Http;

use Tests\Concerns\CreatesTestingSchema;
use Tests\TestCase;

class InternalChatControllerTest extends TestCase
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

    public function test_internal_chat_endpoint_returns_menu_for_first_message(): void
    {
        $this->postJson('/api/internal/chat/messages', [
            'participant_id' => 'dev-user-1',
            'text' => 'hola',
        ])
            ->assertOk()
            ->assertJsonPath('conversation.channel', 'internal_chat')
            ->assertJsonPath('conversation.current_step', 'menu_principal')
            ->assertJsonCount(2, 'outbound_messages')
            ->assertJsonPath('outbound_messages.0.type', 'text')
            ->assertJsonPath('outbound_messages.1.type', 'menu');
    }

    public function test_internal_chat_endpoint_advances_flow_with_numeric_option(): void
    {
        $this->postJson('/api/internal/chat/messages', [
            'participant_id' => 'dev-user-2',
            'text' => 'hola',
        ])->assertOk();

        $this->postJson('/api/internal/chat/messages', [
            'participant_id' => 'dev-user-2',
            'text' => '2',
        ])
            ->assertOk()
            ->assertJsonPath('conversation.current_step', 'identificacion_nombre')
            ->assertJsonPath('conversation.flow_type', 'inasistencia')
            ->assertJsonCount(1, 'outbound_messages')
            ->assertJsonPath('outbound_messages.0.type', 'text')
            ->assertJsonPath('outbound_messages.0.text', __('whatsapp.identificacion.nombre_completo'));
    }
}
