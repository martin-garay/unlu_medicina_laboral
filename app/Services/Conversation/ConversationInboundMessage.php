<?php

namespace App\Services\Conversation;

class ConversationInboundMessage
{
    public function __construct(
        public readonly string $channel,
        public readonly string $participantId,
        public readonly ?string $text = null,
        public readonly ?string $buttonId = null,
        public readonly ?string $providerMessageId = null,
        public readonly string $incomingMessageType = 'text',
        public readonly ?array $media = null,
        public readonly ?array $rawPayload = null,
        public readonly ?string $content = null,
    ) {
    }

    public function toFlowInput(): array
    {
        return [
            'text' => $this->text ?? '',
            'button_id' => $this->buttonId,
            'provider_message_id' => $this->providerMessageId,
            'incoming_message_type' => $this->incomingMessageType,
            'media' => $this->media,
        ];
    }
}
