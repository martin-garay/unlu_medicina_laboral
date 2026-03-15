<?php

namespace App\Services;

use App\Models\Conversacion;
use App\Models\ConversacionEvento;
use Illuminate\Support\Str;

class ConversationEventService
{
    public function record(Conversacion $conversation, string $eventType, array $data = []): ConversacionEvento
    {
        return ConversacionEvento::create([
            'uuid' => (string) Str::uuid(),
            'conversacion_id' => $conversation->id,
            'tipo_evento' => $eventType,
            'step_key' => $data['step_key'] ?? $conversation->currentStepKey(),
            'descripcion' => $data['descripcion'] ?? null,
            'codigo' => $data['codigo'] ?? null,
            'metadata' => $data['metadata'] ?? null,
        ]);
    }

    public function recordStateChange(
        Conversacion $conversation,
        string $fromState,
        string $toState,
        array $metadata = [],
    ): ConversacionEvento {
        return $this->record($conversation, 'state_changed', [
            'descripcion' => sprintf('Cambio de estado de %s a %s', $fromState, $toState),
            'codigo' => 'state_changed',
            'metadata' => array_merge([
                'from_state' => $fromState,
                'to_state' => $toState,
            ], $metadata),
        ]);
    }

    public function recordConversationClosed(
        Conversacion $conversation,
        string $reason,
        array $metadata = [],
    ): ConversacionEvento {
        return $this->record($conversation, 'conversation_closed', [
            'descripcion' => 'Conversacion cerrada',
            'codigo' => $reason,
            'metadata' => $metadata,
        ]);
    }
}
