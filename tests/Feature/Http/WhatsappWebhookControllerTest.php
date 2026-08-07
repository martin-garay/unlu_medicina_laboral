<?php

namespace Tests\Feature\Http;

use Tests\Concerns\CreatesTestingSchema;
use Tests\TestCase;

class WhatsappWebhookControllerTest extends TestCase
{
    use CreatesTestingSchema;

    protected function setUp(): void
    {
        parent::setUp();
        $this->createTestingSchema();
    }

    public function test_status_callback_without_message_is_ignored_without_creating_conversation(): void
    {
        $response = $this->postJson('/api/whatsapp/webhook', [
            'entry' => [
                [
                    'changes' => [
                        [
                            'value' => [
                                'statuses' => [
                                    [
                                        'id' => 'wamid-status-1',
                                        'status' => 'delivered',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('status', 'no_message');

        $this->assertDatabaseCount('conversaciones', 0);
        $this->assertDatabaseCount('conversacion_mensajes', 0);
    }

    public function test_webhook_verification_uses_cached_configuration(): void
    {
        config()->set('medicina_laboral.whatsapp.verify_token', 'verify-from-config');

        $this->get('/api/whatsapp/webhook?hub_mode=subscribe&hub_verify_token=verify-from-config&hub_challenge=12345')
            ->assertOk()
            ->assertSeeText('12345');
    }
}
