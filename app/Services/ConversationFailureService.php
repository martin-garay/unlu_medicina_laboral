<?php

namespace App\Services;

use App\Flows\Common\StepResult;
use App\Models\Conversacion;

class ConversationFailureService
{
    public function __construct(
        private readonly ConversationEventService $conversationEventService,
    ) {
    }

    public function recordInvalidStep(Conversacion $conversation, StepResult $stepResult, array $context = []): void
    {
        if ($stepResult->isValid || $stepResult->errorCode === null) {
            return;
        }

        $this->conversationEventService->recordValidationFailed($conversation, $stepResult->errorCode, array_merge([
            'attempt_increment' => $stepResult->incrementAttempts,
            'current_attempts' => $conversation->cantidad_intentos_actual,
            'total_attempts' => $conversation->cantidad_intentos_totales,
        ], $context));

        if ($stepResult->incrementAttempts > 0 && !$stepResult->closesConversation()) {
            $this->conversationEventService->recordRetryIncremented($conversation, [
                'error_code' => $stepResult->errorCode,
                'current_attempts' => $conversation->cantidad_intentos_actual,
                'total_attempts' => $conversation->cantidad_intentos_totales,
                'max_invalid_attempts' => $this->maxInvalidAttempts(),
            ]);
        }
    }

    public function enforceAttemptLimit(Conversacion $conversation, StepResult $stepResult): StepResult
    {
        if ($stepResult->isValid || $stepResult->incrementAttempts <= 0 || $stepResult->closesConversation()) {
            return $stepResult;
        }

        if ($conversation->cantidad_intentos_actual < $this->maxInvalidAttempts()) {
            return $stepResult;
        }

        return StepResult::invalid('max_attempts_exceeded', 'whatsapp.errores.max_attempts_exceeded', [
            'should_cancel' => true,
            'payload' => [
                'event_name' => 'max_attempts_exceeded',
                'event_step_key' => $conversation->currentStepKey(),
                'event_description' => 'Se superó el máximo de intentos inválidos permitido',
                'event_metadata' => [
                    'error_code' => $stepResult->errorCode,
                    'current_attempts' => $conversation->cantidad_intentos_actual,
                    'total_attempts' => $conversation->cantidad_intentos_totales,
                    'max_invalid_attempts' => $this->maxInvalidAttempts(),
                ],
                'close_reason' => 'max_invalid_attempts',
                'close_attributes' => [
                    'estado' => 'cancelada',
                    'estado_actual' => 'cancelada',
                    'paso_actual' => 'cancelada',
                ],
            ],
        ]);
    }

    private function maxInvalidAttempts(): int
    {
        return (int) config('medicina_laboral.conversation.max_invalid_attempts', 3);
    }
}
