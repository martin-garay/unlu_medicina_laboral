<?php

namespace App\Services;

use App\Models\Conversacion;
use App\Models\ConversacionMensaje;
use Carbon\CarbonInterface;
use Illuminate\Support\Str;

class ConversationMessageService
{
    public function __construct(
        private readonly ConversationManager $conversationManager,
    ) {
    }

    public function registerIncomingMessage(Conversacion $conversation, array $data): ConversacionMensaje
    {
        $message = ConversacionMensaje::create([
            'uuid' => (string) Str::uuid(),
            'conversacion_id' => $conversation->id,
            'direccion' => ConversacionMensaje::DIRECCION_ENTRANTE,
            'provider_message_id' => $data['provider_message_id'] ?? null,
            'tipo_mensaje' => $data['tipo_mensaje'] ?? 'text',
            'step_key' => $data['step_key'] ?? $conversation->currentStepKey(),
            'contenido_texto' => $data['contenido_texto'] ?? null,
            'es_valido' => $data['es_valido'] ?? null,
            'motivo_invalidez' => $data['motivo_invalidez'] ?? null,
            'message_key' => $data['message_key'] ?? null,
            'template_name' => $data['template_name'] ?? null,
            'payload_crudo' => $data['payload_crudo'] ?? null,
            'metadata' => $data['metadata'] ?? null,
        ]);

        $this->conversationManager->incrementIncomingCounters(
            $conversation,
            $data['es_valido'] ?? null,
            $data['incrementar_intentos'] ?? 0,
            $this->resolveTimestamp($data['created_at'] ?? null),
        );

        return $message;
    }

    public function registerOutgoingMessage(Conversacion $conversation, array $data): ConversacionMensaje
    {
        $message = ConversacionMensaje::create([
            'uuid' => (string) Str::uuid(),
            'conversacion_id' => $conversation->id,
            'direccion' => ConversacionMensaje::DIRECCION_SALIENTE,
            'provider_message_id' => $data['provider_message_id'] ?? null,
            'tipo_mensaje' => $data['tipo_mensaje'] ?? 'text',
            'step_key' => $data['step_key'] ?? $conversation->currentStepKey(),
            'contenido_texto' => $data['contenido_texto'] ?? null,
            'es_valido' => $data['es_valido'] ?? null,
            'motivo_invalidez' => $data['motivo_invalidez'] ?? null,
            'message_key' => $data['message_key'] ?? null,
            'template_name' => $data['template_name'] ?? null,
            'payload_crudo' => $data['payload_crudo'] ?? null,
            'metadata' => $data['metadata'] ?? null,
        ]);

        $this->conversationManager->incrementOutgoingCounters(
            $conversation,
            $this->resolveTimestamp($data['created_at'] ?? null),
        );

        return $message;
    }

    private function resolveTimestamp(CarbonInterface|string|null $timestamp): CarbonInterface|string|null
    {
        return $timestamp;
    }
}
