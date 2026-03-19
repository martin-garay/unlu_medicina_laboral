<?php

namespace Tests\Feature\Services;

use App\Flows\Common\StepResult;
use App\Services\ConversationFailureService;
use App\Services\ConversationManager;
use Tests\Concerns\CreatesTestingSchema;
use Tests\TestCase;

class ConversationFailureServiceTest extends TestCase
{
    use CreatesTestingSchema;

    protected function setUp(): void
    {
        parent::setUp();
        $this->createTestingSchema();
    }

    public function test_record_invalid_step_creates_validation_and_retry_events(): void
    {
        $conversation = app(ConversationManager::class)->createConversation('5491111111111', [
            'paso_actual' => 'identificacion_legajo',
        ]);

        $conversation = app(ConversationManager::class)->incrementIncomingCounters($conversation, false, 1);

        app(ConversationFailureService::class)->recordInvalidStep(
            $conversation,
            StepResult::invalid('legajo_invalido', 'whatsapp.errores.legajo_invalido', [
                'increment_attempts' => 1,
            ]),
            ['incoming_message_type' => 'text']
        );

        $this->assertDatabaseHas('conversacion_eventos', [
            'conversacion_id' => $conversation->id,
            'tipo_evento' => 'validation_failed',
            'codigo' => 'legajo_invalido',
        ]);
        $this->assertDatabaseHas('conversacion_eventos', [
            'conversacion_id' => $conversation->id,
            'tipo_evento' => 'retry_incremented',
            'codigo' => 'retry_incremented',
        ]);
    }

    public function test_record_invalid_step_skips_events_for_valid_result(): void
    {
        $conversation = app(ConversationManager::class)->createConversation('5491111111111');

        app(ConversationFailureService::class)->recordInvalidStep($conversation, StepResult::make());

        $this->assertDatabaseCount('conversacion_eventos', 0);
    }

    public function test_enforce_attempt_limit_returns_original_result_when_below_threshold(): void
    {
        config()->set('medicina_laboral.conversation.max_invalid_attempts', 3);

        $conversation = app(ConversationManager::class)->createConversation('5491111111111', [
            'cantidad_intentos_actual' => 2,
            'cantidad_intentos_totales' => 2,
            'paso_actual' => 'identificacion_legajo',
        ]);

        $stepResult = StepResult::invalid('legajo_invalido', 'whatsapp.errores.legajo_invalido', [
            'increment_attempts' => 1,
        ]);

        $resolved = app(ConversationFailureService::class)->enforceAttemptLimit($conversation, $stepResult);

        $this->assertSame($stepResult, $resolved);
    }

    public function test_enforce_attempt_limit_returns_cancelling_result_when_threshold_is_reached(): void
    {
        config()->set('medicina_laboral.conversation.max_invalid_attempts', 3);

        $conversation = app(ConversationManager::class)->createConversation('5491111111111', [
            'cantidad_intentos_actual' => 3,
            'cantidad_intentos_totales' => 3,
            'paso_actual' => 'identificacion_legajo',
        ]);

        $stepResult = StepResult::invalid('legajo_invalido', 'whatsapp.errores.legajo_invalido', [
            'increment_attempts' => 1,
        ]);

        $resolved = app(ConversationFailureService::class)->enforceAttemptLimit($conversation, $stepResult);

        $this->assertFalse($resolved->isValid);
        $this->assertSame('max_attempts_exceeded', $resolved->errorCode);
        $this->assertTrue($resolved->shouldCancel);
        $this->assertSame('max_attempts_exceeded', $resolved->payload['event_name']);
        $this->assertSame('max_invalid_attempts', $resolved->payload['close_reason']);
    }
}
