<?php

namespace App\Services\Conversation;

use App\Models\Conversacion;
use App\Services\Conversation\Contracts\ConversationChannelSender;
use App\Services\WhatsAppSender;

class ConversationChannelRouter implements ConversationChannelSender
{
    public function __construct(
        private readonly WhatsAppSender $whatsAppSender,
    ) {
    }

    public function sendText(string $channel, string $participantId, string $message): void
    {
        match ($channel) {
            Conversacion::CANAL_WHATSAPP => $this->whatsAppSender->sendText($participantId, $message),
            default => throw new \InvalidArgumentException(sprintf('Unsupported channel sender for [%s].', $channel)),
        };
    }

    public function sendMenu(string $channel, string $participantId, array $menuConfig): void
    {
        match ($channel) {
            Conversacion::CANAL_WHATSAPP => $this->whatsAppSender->sendInteractiveMenu($participantId, $menuConfig),
            default => throw new \InvalidArgumentException(sprintf('Unsupported channel sender for [%s].', $channel)),
        };
    }
}
