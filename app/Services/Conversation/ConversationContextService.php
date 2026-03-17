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

    public function resetIdentification(Conversacion $conversation): array
    {
        $metadata = $this->metadata($conversation);

        Arr::set($metadata, 'identificacion', []);

        return [
            'metadata' => $metadata,
            'dni' => null,
        ];
    }

    private function metadata(Conversacion $conversation): array
    {
        return is_array($conversation->metadata) ? $conversation->metadata : [];
    }
}
