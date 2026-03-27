<?php

namespace App\Services\Conversation;

use App\Models\Conversacion;

class ConversationInteractionResult
{
    /**
     * @param array<ConversationOutboundMessage> $outboundMessages
     */
    public function __construct(
        public readonly Conversacion $conversation,
        public readonly array $outboundMessages = [],
    ) {
    }
}
