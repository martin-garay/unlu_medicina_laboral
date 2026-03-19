<?php

namespace Tests\Feature\Services;

use App\Models\Conversacion;
use App\Services\ConversationManager;
use Carbon\Carbon;
use Tests\Concerns\CreatesTestingSchema;
use Tests\TestCase;

class ConversationManagerTest extends TestCase
{
    use CreatesTestingSchema;

    protected function setUp(): void
    {
        parent::setUp();
        $this->createTestingSchema();
    }

    public function test_create_conversation_sets_default_attributes(): void
    {
        $conversation = app(ConversationManager::class)->createConversation('5491111111111');

        $this->assertSame('5491111111111', $conversation->wa_number);
        $this->assertSame('menu_principal', $conversation->estado_actual);
        $this->assertTrue($conversation->activa);
    }

    public function test_increment_incoming_counters_updates_counts_and_attempts(): void
    {
        $manager = app(ConversationManager::class);
        $conversation = $manager->createConversation('5491111111111');
        $timestamp = Carbon::parse('2026-03-19 10:00:00');

        $updated = $manager->incrementIncomingCounters($conversation, false, 2, $timestamp);

        $this->assertSame(1, $updated->cantidad_mensajes_recibidos);
        $this->assertSame(1, $updated->cantidad_mensajes_invalidos);
        $this->assertSame(2, $updated->cantidad_intentos_actual);
        $this->assertSame('2026-03-19 10:00:00', $updated->ultimo_mensaje_recibido_en?->format('Y-m-d H:i:s'));
    }

    public function test_close_conversation_marks_it_inactive_and_sets_reason(): void
    {
        $manager = app(ConversationManager::class);
        $conversation = $manager->createConversation('5491111111111');

        $closed = $manager->closeConversation($conversation, 'completed', [
            'estado_actual' => 'completada',
        ]);

        $this->assertFalse($closed->activa);
        $this->assertSame('completed', $closed->motivo_finalizacion);
        $this->assertSame('completada', $closed->estado_actual);
        $this->assertNotNull($closed->finalizada_en);
    }
}
