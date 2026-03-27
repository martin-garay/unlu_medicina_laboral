<?php

namespace App\Http\Controllers;

use App\Models\Conversacion;
use App\Services\Conversation\ConversationInboundMessage;
use App\Services\Conversation\ConversationInteractionService;
use Illuminate\Http\Request;

class InternalChatController extends Controller
{
    public function __construct(
        private readonly ConversationInteractionService $conversationInteractionService,
    ) {
    }

    public function send(Request $request)
    {
        $validated = $request->validate([
            'participant_id' => ['required', 'string'],
            'text' => ['nullable', 'string'],
            'button_id' => ['nullable', 'string'],
        ]);

        $interaction = $this->conversationInteractionService->handleInboundMessage(
            new ConversationInboundMessage(
                channel: Conversacion::CANAL_INTERNO,
                participantId: $validated['participant_id'],
                text: $validated['text'] ?? null,
                buttonId: $validated['button_id'] ?? null,
                providerMessageId: null,
                incomingMessageType: ($validated['button_id'] ?? null) !== null ? 'button' : 'text',
                rawPayload: $validated,
                content: $validated['text'] ?? $validated['button_id'] ?? null,
            )
        );

        return response()->json([
            'conversation' => [
                'id' => $interaction->conversation->id,
                'channel' => $interaction->conversation->canal,
                'participant_id' => $interaction->conversation->wa_number,
                'current_step' => $interaction->conversation->currentStepKey(),
                'flow_type' => $interaction->conversation->tipo_flujo,
                'active' => $interaction->conversation->activa,
            ],
            'outbound_messages' => array_map(
                static fn ($message): array => [
                    'type' => $message->type,
                    'text' => $message->text,
                    'menu' => $message->menuConfig,
                ],
                $interaction->outboundMessages
            ),
        ]);
    }
}
