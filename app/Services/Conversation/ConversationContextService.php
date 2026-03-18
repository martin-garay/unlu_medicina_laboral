<?php

namespace App\Services\Conversation;

use App\Models\Conversacion;
use Illuminate\Support\Arr;

class ConversationContextService
{
    public function identificationData(Conversacion $conversation): array
    {
        return Arr::get($this->metadata($conversation), 'identificacion', []);
    }

    public function withIdentificationData(Conversacion $conversation, array $updates): array
    {
        $metadata = $this->metadata($conversation);
        $identification = array_merge($this->identificationData($conversation), $updates);

        Arr::set($metadata, 'identificacion', $identification);

        return [
            'metadata' => $metadata,
        ];
    }

    public function avisoData(Conversacion $conversation): array
    {
        return Arr::get($this->metadata($conversation), 'aviso', []);
    }

    public function withAvisoData(Conversacion $conversation, array $updates): array
    {
        $metadata = $this->metadata($conversation);
        $aviso = array_merge($this->avisoData($conversation), $updates);

        Arr::set($metadata, 'aviso', $aviso);

        return [
            'metadata' => $metadata,
        ];
    }

    public function resetIdentification(Conversacion $conversation): array
    {
        $metadata = $this->metadata($conversation);

        Arr::set($metadata, 'identificacion', []);

        return [
            'metadata' => $metadata,
            'dni' => null,
        ];
    }

    public function resetAviso(Conversacion $conversation): array
    {
        $metadata = $this->metadata($conversation);

        Arr::set($metadata, 'aviso', []);

        return [
            'metadata' => $metadata,
        ];
    }

    public function resetCurrentFlowContext(Conversacion $conversation): array
    {
        $metadata = $this->metadata($conversation);

        Arr::set($metadata, 'identificacion', []);
        Arr::set($metadata, 'aviso', []);

        return [
            'metadata' => $metadata,
            'dni' => null,
            'tipo' => null,
            'tipo_flujo' => null,
        ];
    }

    private function metadata(Conversacion $conversation): array
    {
        return is_array($conversation->metadata) ? $conversation->metadata : [];
    }
}
