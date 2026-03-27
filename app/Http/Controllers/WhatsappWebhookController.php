<?php

namespace App\Http\Controllers;

use App\Models\Conversacion;
use App\Services\Conversation\ConversationInboundMessage;
use App\Services\Conversation\ConversationInteractionService;
use App\Services\Conversation\ConversationOutboundMessage;
use App\Services\Conversation\Contracts\ConversationChannelSender;
use App\Services\ConversationEventService;
use App\Services\ConversationMessageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WhatsappWebhookController extends Controller
{
    public function __construct(
        private readonly ConversationChannelSender $channelSender,
        private readonly ConversationInteractionService $conversationInteractionService,
        private readonly ConversationMessageService $conversationMessageService,
        private readonly ConversationEventService $conversationEventService,
    ) {
    }

    /**
     * Verificación de webhook (GET).
     */
    public function verify(Request $request)
    {
        $mode = $request->query('hub_mode');
        $verifyToken = $request->query('hub_verify_token');
        $challenge = $request->query('hub_challenge');

        if ($mode && $verifyToken && $mode === 'subscribe' && $verifyToken === env('WHATSAPP_VERIFY_TOKEN')) {
            return response($challenge, 200);
        }

        return response('Error: token inválido.', 403);
    }

    /**
     * Recepción de mensajes entrantes (POST).
     */
    public function receive(Request $request)
    {
        Log::info('Webhook payload', $request->all());

        $entry = $request->input('entry.0.changes.0.value.messages.0');
        if (!$entry) {
            return response()->json(['status' => 'no_message'], 200);
        }

        $from = $entry['from'] ?? null;
        $text = $entry['text']['body'] ?? '';
        $buttonId = $entry['interactive']['button_reply']['id'] ?? null;
        $providerMessageId = $entry['id'] ?? null;
        $incomingMessageType = $this->resolveIncomingMessageType($entry);

        if (!$from) {
            return response()->json(['status' => 'no_sender'], 200);
        }

        $interaction = $this->conversationInteractionService->handleInboundMessage(
            new ConversationInboundMessage(
                channel: Conversacion::CANAL_WHATSAPP,
                participantId: $from,
                text: $text,
                buttonId: $buttonId,
                providerMessageId: $providerMessageId,
                incomingMessageType: $incomingMessageType,
                media: $this->extractMediaMetadata($entry),
                rawPayload: $entry,
                content: $this->resolveIncomingContent($entry),
            )
        );

        $this->dispatchOutboundMessages($interaction->conversation, $from, $interaction->outboundMessages);

        return response()->json(['status' => 'ok']);
    }

    /**
     * @param array<ConversationOutboundMessage> $messages
     */
    private function dispatchOutboundMessages(Conversacion $conversation, string $to, array $messages): void
    {
        foreach ($messages as $message) {
            if ($message->isMenu()) {
                $this->sendMenuResponse($conversation, $to, $message);
                continue;
            }

            $this->sendTextResponse($conversation, $to, $message);
        }
    }

    private function sendTextResponse(Conversacion $conversation, string $to, ConversationOutboundMessage $message): void
    {
        $this->channelSender->sendText((string) $conversation->canal, $to, (string) $message->text);

        $this->conversationMessageService->registerOutgoingMessage($conversation, [
            'tipo_mensaje' => $message->traceMessageType(),
            'step_key' => $conversation->currentStepKey(),
            'contenido_texto' => $message->traceContent(),
            'payload_crudo' => $message->tracePayload(),
            'metadata' => [
                'transport' => 'whatsapp_cloud_api',
            ],
        ]);

        $this->conversationEventService->record($conversation, 'outgoing_message_sent', [
            'step_key' => $conversation->currentStepKey(),
            'descripcion' => 'Mensaje saliente enviado',
            'metadata' => [
                'tipo_mensaje' => $message->traceMessageType(),
            ],
        ]);
    }

    private function sendMenuResponse(Conversacion $conversation, string $to, ConversationOutboundMessage $message): void
    {
        $menuConfig = $message->menuConfig;
        $this->channelSender->sendMenu((string) $conversation->canal, $to, $menuConfig);

        $this->conversationMessageService->registerOutgoingMessage($conversation, [
            'tipo_mensaje' => $message->traceMessageType(),
            'step_key' => $conversation->currentStepKey(),
            'contenido_texto' => $message->traceContent(),
            'payload_crudo' => $message->tracePayload(),
            'metadata' => [
                'transport' => 'whatsapp_cloud_api',
            ],
        ]);

        $this->conversationEventService->record($conversation, 'outgoing_message_sent', [
            'step_key' => $conversation->currentStepKey(),
            'descripcion' => 'Menu interactivo enviado',
            'metadata' => [
                'tipo_mensaje' => 'interactive',
            ],
        ]);
    }

    private function resolveIncomingMessageType(array $entry): string
    {
        if (isset($entry['interactive']['button_reply'])) {
            return 'button';
        }

        if (isset($entry['interactive'])) {
            return 'interactive';
        }

        if (isset($entry['text'])) {
            return 'text';
        }

        if (isset($entry['document'])) {
            return 'document';
        }

        if (isset($entry['image'])) {
            return 'image';
        }

        return 'unknown';
    }

    private function resolveIncomingContent(array $entry): ?string
    {
        if (isset($entry['text']['body'])) {
            return $entry['text']['body'];
        }

        if (isset($entry['interactive']['button_reply']['title'])) {
            return $entry['interactive']['button_reply']['title'];
        }

        if (isset($entry['interactive']['button_reply']['id'])) {
            return $entry['interactive']['button_reply']['id'];
        }

        if (isset($entry['document']['filename'])) {
            return $entry['document']['filename'];
        }

        if (isset($entry['image']['caption'])) {
            return $entry['image']['caption'];
        }

        return null;
    }

    private function extractMediaMetadata(array $entry): ?array
    {
        if (isset($entry['document'])) {
            return [
                'provider_media_id' => $entry['document']['id'] ?? null,
                'mime_type' => $entry['document']['mime_type'] ?? null,
                'sha256' => $entry['document']['sha256'] ?? null,
                'filename' => $entry['document']['filename'] ?? null,
                'caption' => $entry['document']['caption'] ?? null,
                'source_type' => 'document',
            ];
        }

        if (isset($entry['image'])) {
            return [
                'provider_media_id' => $entry['image']['id'] ?? null,
                'mime_type' => $entry['image']['mime_type'] ?? null,
                'sha256' => $entry['image']['sha256'] ?? null,
                'filename' => null,
                'caption' => $entry['image']['caption'] ?? null,
                'source_type' => 'image',
            ];
        }

        return null;
    }
}
