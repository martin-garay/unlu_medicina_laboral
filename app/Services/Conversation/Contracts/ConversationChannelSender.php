<?php

namespace App\Services\Conversation\Contracts;

interface ConversationChannelSender
{
    public function sendText(string $channel, string $participantId, string $message): void;

    public function sendMenu(string $channel, string $participantId, array $menuConfig): void;
}
