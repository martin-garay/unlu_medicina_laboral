<?php

namespace App\Http\Controllers;

use App\Models\Aviso;
use App\Models\Conversacion;
use App\Flows\Common\MessageResolver;
use App\Flows\Common\StepResult;
use App\Services\Conversation\ConversationFlowResolver;
use App\Services\ConversationEventService;
use App\Services\ConversationManager;
use App\Services\ConversationMessageService;
use App\Services\WhatsAppSender;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WhatsappWebhookController extends Controller
{
    public function __construct(
        private readonly WhatsAppSender $whatsAppSender,
        private readonly ConversationFlowResolver $conversationFlowResolver,
        private readonly MessageResolver $messageResolver,
        private readonly ConversationManager $conversationManager,
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

        $conversation = $this->findOrCreateConversation($from, $providerMessageId);
        $flowInput = $this->buildFlowInput($text, $buttonId, $providerMessageId, $incomingMessageType);
        $stepResult = $this->processConversationStep($conversation, $flowInput);

        $this->registerIncomingTrace($conversation, $entry, $from, $buttonId, $providerMessageId, $incomingMessageType, $stepResult);
        $stepResult = $this->enforceAttemptLimit($conversation, $stepResult);
        $conversation = $this->applyStepResult($conversation, $stepResult);
        $this->recordStepResultEvent($conversation, $stepResult);
        $this->dispatchStepResponse($conversation, $from, $stepResult);

        return response()->json(['status' => 'ok']);
    }

    private function buildFlowInput(
        string $text,
        ?string $buttonId,
        ?string $providerMessageId,
        string $incomingMessageType,
    ): array {
        return [
            'text' => $text,
            'button_id' => $buttonId,
            'provider_message_id' => $providerMessageId,
            'incoming_message_type' => $incomingMessageType,
        ];
    }

    private function findOrCreateConversation(string $from, ?string $providerMessageId): Conversacion
    {
        $conversation = $this->conversationManager->findActiveByWaNumber($from);

        if ($conversation) {
            return $conversation;
        }

        $conversation = $this->conversationManager->createConversation($from, [
            'estado' => 'menu_principal',
            'estado_actual' => 'menu_principal',
            'paso_actual' => 'menu_principal',
        ]);

        $this->conversationEventService->record($conversation, 'conversation_started', [
            'step_key' => $conversation->paso_actual,
            'descripcion' => 'Conversacion iniciada desde webhook',
            'metadata' => [
                'wa_number' => $from,
                'provider_message_id' => $providerMessageId,
            ],
        ]);

        return $conversation;
    }

    private function processConversationStep(Conversacion $conversation, array $flowInput): StepResult
    {
        return $this->conversationFlowResolver
            ->resolve($conversation)
            ->handle($conversation, $flowInput);
    }

    private function registerIncomingTrace(
        Conversacion $conversation,
        array $entry,
        string $from,
        ?string $buttonId,
        ?string $providerMessageId,
        string $incomingMessageType,
        StepResult $stepResult,
    ): void {
        $this->conversationMessageService->registerIncomingMessage($conversation, [
            'provider_message_id' => $providerMessageId,
            'tipo_mensaje' => $incomingMessageType,
            'step_key' => $conversation->currentStepKey(),
            'contenido_texto' => $this->resolveIncomingContent($entry),
            'es_valido' => $stepResult->isValid,
            'motivo_invalidez' => $stepResult->isValid ? null : $stepResult->errorCode,
            'incrementar_intentos' => $stepResult->incrementAttempts,
            'payload_crudo' => $entry,
            'metadata' => [
                'button_id' => $buttonId,
                'from' => $from,
            ],
        ]);

        $this->conversationEventService->record($conversation, 'incoming_message_received', [
            'step_key' => $conversation->currentStepKey(),
            'descripcion' => 'Mensaje entrante recibido',
            'metadata' => [
                'provider_message_id' => $providerMessageId,
                'tipo_mensaje' => $incomingMessageType,
                'button_id' => $buttonId,
            ],
        ]);
    }

    private function transitionConversation(Conversacion $conversation, string $newState): Conversacion
    {
        $previousState = $conversation->estado;

        $conversation = $this->conversationManager->transitionConversation($conversation, $newState);

        $this->conversationEventService->recordStateChange(
            $conversation,
            (string) $previousState,
            $newState
        );

        return $conversation;
    }

    private function applyStepResult(Conversacion $conversation, StepResult $stepResult): Conversacion
    {
        $conversation = $conversation->refresh();
        $conversation = $this->applyConversationUpdates(
            $conversation,
            $stepResult->payload['conversation_updates'] ?? []
        );

        if ($stepResult->shouldCancel) {
            $reason = $stepResult->payload['close_reason'] ?? 'cancelled';
            $conversation = $this->conversationManager->closeConversation(
                $conversation,
                $reason,
                $stepResult->payload['close_attributes'] ?? []
            );

            $this->conversationEventService->recordConversationClosed($conversation, $reason, [
                'error_code' => $stepResult->errorCode,
            ]);

            return $conversation;
        }

        if ($stepResult->shouldFinish) {
            $this->executeBusinessAction($conversation, $stepResult);

            $reason = $stepResult->payload['close_reason'] ?? 'completed';
            $conversation = $this->conversationManager->closeConversation(
                $conversation,
                $reason,
                $stepResult->payload['close_attributes'] ?? []
            );

            $this->conversationEventService->recordConversationClosed($conversation, $reason, [
                'error_code' => $stepResult->errorCode,
            ]);

            return $conversation;
        }

        if ($stepResult->hasNextState()) {
            return $this->transitionConversation($conversation, $stepResult->nextState);
        }

        return $conversation;
    }

    private function enforceAttemptLimit(Conversacion $conversation, StepResult $stepResult): StepResult
    {
        if ($stepResult->isValid || $stepResult->incrementAttempts <= 0 || $stepResult->closesConversation()) {
            return $stepResult;
        }

        $conversation = $conversation->refresh();
        $maxInvalidAttempts = (int) config('medicina_laboral.conversation.max_invalid_attempts', 3);

        if ($conversation->cantidad_intentos_actual < $maxInvalidAttempts) {
            return $stepResult;
        }

        return StepResult::invalid('max_attempts_exceeded', 'whatsapp.errores.max_attempts_exceeded', [
            'should_cancel' => true,
            'payload' => [
                'close_reason' => 'max_invalid_attempts',
                'close_attributes' => [
                    'estado' => 'cancelada',
                    'estado_actual' => 'cancelada',
                    'paso_actual' => 'cancelada',
                ],
            ],
        ]);
    }

    private function dispatchStepResponse(Conversacion $conversation, string $to, StepResult $stepResult): void
    {
        $responseText = $this->resolveStepMessage($stepResult);

        if ($responseText !== null) {
            $this->sendTextResponse($conversation, $to, $responseText);
        }

        if ($stepResult->shouldShowMenu) {
            $this->sendMenuResponse($conversation, $to, $this->mainMenuConfig());
        }
    }

    private function recordStepResultEvent(Conversacion $conversation, StepResult $stepResult): void
    {
        $eventName = $stepResult->payload['event_name'] ?? null;

        if ($eventName === null) {
            return;
        }

        $this->conversationEventService->record($conversation, $eventName, [
            'step_key' => $stepResult->payload['event_step_key'] ?? $conversation->currentStepKey(),
            'descripcion' => $stepResult->payload['event_description'] ?? 'Evento del flujo conversacional',
            'metadata' => $stepResult->payload['event_metadata'] ?? [],
        ]);
    }

    private function applyConversationUpdates(Conversacion $conversation, array $updates): Conversacion
    {
        if ($updates === []) {
            return $conversation;
        }

        $conversation->forceFill($updates)->save();

        return $conversation->refresh();
    }

    private function executeBusinessAction(Conversacion $conversation, StepResult $stepResult): void
    {
        $action = $stepResult->payload['business_action'] ?? null;

        if ($action === 'create_aviso_inasistencia') {
            Aviso::create([
                'dni' => $conversation->dni,
                'tipo' => 'inasistencia',
                'fecha_inicio' => now()->toDateString(),
                'cantidad_dias' => $stepResult->payload['cantidad_dias'] ?? null,
                'wa_number' => $conversation->wa_number,
            ]);

            return;
        }

        if ($action === 'create_aviso_certificado') {
            Aviso::create([
                'dni' => $conversation->dni,
                'tipo' => 'certificado',
                'certificado_base64' => $stepResult->payload['certificado_texto'] ?? null,
                'wa_number' => $conversation->wa_number,
            ]);
        }
    }

    private function sendTextResponse(Conversacion $conversation, string $to, string $message): void
    {
        $this->whatsAppSender->sendText($to, $message);

        $this->conversationMessageService->registerOutgoingMessage($conversation, [
            'tipo_mensaje' => 'text',
            'step_key' => $conversation->currentStepKey(),
            'contenido_texto' => $message,
            'payload_crudo' => [
                'type' => 'text',
                'text' => ['body' => $message],
            ],
            'metadata' => [
                'transport' => 'whatsapp_cloud_api',
            ],
        ]);

        $this->conversationEventService->record($conversation, 'outgoing_message_sent', [
            'step_key' => $conversation->currentStepKey(),
            'descripcion' => 'Mensaje saliente enviado',
            'metadata' => [
                'tipo_mensaje' => 'text',
            ],
        ]);
    }

    private function sendMenuResponse(Conversacion $conversation, string $to, array $menuConfig): void
    {
        $this->whatsAppSender->sendInteractiveMenu($to, $menuConfig);

        $this->conversationMessageService->registerOutgoingMessage($conversation, [
            'tipo_mensaje' => 'interactive',
            'step_key' => $conversation->currentStepKey(),
            'contenido_texto' => $menuConfig['body_text'] ?? null,
            'payload_crudo' => [
                'type' => 'interactive',
                'interactive' => [
                    'type' => 'button',
                    'body' => ['text' => $menuConfig['body_text'] ?? ''],
                    'buttons' => $menuConfig['buttons'] ?? [],
                ],
            ],
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

        return null;
    }

    private function mainMenuConfig(): array
    {
        $options = config(
            'medicina_laboral.mensajes.menu_principal_options',
            config('medicina_laboral.mensajes.current_webhook_menu_options', [])
        );
        $catalog = config('medicina_laboral.catalogos.menu_principal', []);
        $buttons = [];

        foreach ($options as $optionKey) {
            $option = $catalog[$optionKey] ?? null;

            if (!$option) {
                continue;
            }

            $buttons[] = [
                'id' => $option['id'],
                'title' => __("whatsapp.menu.button_titles.{$optionKey}"),
            ];
        }

        return [
            'body_text' => __('whatsapp.menu.prompt'),
            'buttons' => $buttons,
        ];
    }

    private function resolveStepMessage(StepResult $stepResult): ?string
    {
        return $this->messageResolver->resolve($stepResult);
    }
}
