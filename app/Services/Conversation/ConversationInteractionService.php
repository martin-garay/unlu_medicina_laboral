<?php

namespace App\Services\Conversation;

use App\Flows\Common\MessageResolver;
use App\Flows\Common\StepResult;
use App\Models\Conversacion;
use App\Services\AnticipoCertificadoService;
use App\Services\AvisoService;
use App\Services\ConversationEventService;
use App\Services\ConversationFailureService;
use App\Services\ConversationManager;
use App\Services\ConversationMessageService;
use Illuminate\Support\Facades\Log;

class ConversationInteractionService
{
    public function __construct(
        private readonly ConversationFlowResolver $conversationFlowResolver,
        private readonly MessageResolver $messageResolver,
        private readonly ConversationManager $conversationManager,
        private readonly ConversationMessageService $conversationMessageService,
        private readonly ConversationEventService $conversationEventService,
        private readonly ConversationFailureService $conversationFailureService,
        private readonly AvisoService $avisoService,
        private readonly AnticipoCertificadoService $anticipoCertificadoService,
    ) {
    }

    public function handleInboundMessage(ConversationInboundMessage $message): ConversationInteractionResult
    {
        $conversation = $this->findOrCreateConversationFromInbound($message);
        $stepResult = $this->processConversationStep($conversation, $message->toFlowInput());

        $this->registerIncomingTrace($conversation, $message, $stepResult);

        $conversation = $conversation->refresh();
        $this->conversationFailureService->recordInvalidStep($conversation, $stepResult, [
            'provider_message_id' => $message->providerMessageId,
            'incoming_message_type' => $message->incomingMessageType,
        ]);

        $stepResult = $this->conversationFailureService->enforceAttemptLimit($conversation, $stepResult);
        ['conversation' => $conversation, 'response_result' => $responseResult] = $this->applyStepResult($conversation, $stepResult);
        $this->recordStepResultEvent($conversation, $stepResult);
        $this->logInteractionProcessed($conversation, $message, $stepResult, $responseResult);

        return new ConversationInteractionResult(
            conversation: $conversation,
            outboundMessages: $this->buildOutboundMessages($responseResult ?? $stepResult),
        );
    }

    private function findOrCreateConversationFromInbound(
        ConversationInboundMessage $message,
    ): Conversacion {
        $conversation = $this->conversationManager->findActiveByParticipant($message->channel, $message->participantId);

        if ($conversation) {
            return $conversation;
        }

        $conversation = $this->conversationManager->createConversationForChannel($message->channel, $message->participantId, [
            'estado' => 'menu_principal',
            'estado_actual' => 'menu_principal',
            'paso_actual' => 'menu_principal',
        ]);

        $this->conversationEventService->record($conversation, 'conversation_started', [
            'step_key' => $conversation->paso_actual,
            'descripcion' => 'Conversacion iniciada desde adapter de canal',
            'metadata' => [
                'channel' => $message->channel,
                'participant_id' => $message->participantId,
                'wa_number' => $message->channel === Conversacion::CANAL_WHATSAPP ? $message->participantId : null,
                'provider_message_id' => $message->providerMessageId,
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
        ConversationInboundMessage $message,
        StepResult $stepResult,
    ): void {
        $this->conversationMessageService->registerIncomingMessage($conversation, [
            'provider_message_id' => $message->providerMessageId,
            'tipo_mensaje' => $message->incomingMessageType,
            'step_key' => $conversation->currentStepKey(),
            'contenido_texto' => $message->content,
            'es_valido' => $stepResult->isValid,
            'motivo_invalidez' => $stepResult->isValid ? null : $stepResult->errorCode,
            'incrementar_intentos' => $stepResult->incrementAttempts,
            'payload_crudo' => $message->rawPayload,
            'metadata' => [
                'button_id' => $message->buttonId,
                'channel' => $message->channel,
                'participant_id' => $message->participantId,
            ],
        ]);

        $this->conversationEventService->record($conversation, 'incoming_message_received', [
            'step_key' => $conversation->currentStepKey(),
            'descripcion' => 'Mensaje entrante recibido',
            'metadata' => [
                'channel' => $message->channel,
                'provider_message_id' => $message->providerMessageId,
                'tipo_mensaje' => $message->incomingMessageType,
                'button_id' => $message->buttonId,
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

    private function applyStepResult(Conversacion $conversation, StepResult $stepResult): array
    {
        $conversation = $conversation->refresh();
        $conversation = $this->applyConversationUpdates(
            $conversation,
            $stepResult->payload['conversation_updates'] ?? []
        );
        $responseResult = null;

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

            return [
                'conversation' => $conversation,
                'response_result' => $responseResult,
            ];
        }

        if ($stepResult->shouldFinish) {
            $responseResult = $this->executeBusinessAction($conversation, $stepResult);

            $reason = $stepResult->payload['close_reason'] ?? 'completed';
            $conversation = $this->conversationManager->closeConversation(
                $conversation,
                $reason,
                $stepResult->payload['close_attributes'] ?? []
            );

            $this->conversationEventService->recordConversationClosed($conversation, $reason, [
                'error_code' => $stepResult->errorCode,
            ]);

            return [
                'conversation' => $conversation,
                'response_result' => $responseResult,
            ];
        }

        if ($stepResult->hasNextState()) {
            $conversation = $this->transitionConversation($conversation, $stepResult->nextState);
        }

        return [
            'conversation' => $conversation,
            'response_result' => $responseResult,
        ];
    }

    private function applyConversationUpdates(Conversacion $conversation, array $updates): Conversacion
    {
        if ($updates === []) {
            return $conversation;
        }

        $conversation->forceFill($updates)->save();

        return $conversation->refresh();
    }

    private function executeBusinessAction(Conversacion $conversation, StepResult $stepResult): ?StepResult
    {
        $action = $stepResult->payload['business_action'] ?? null;

        if ($action === 'create_aviso_from_conversation') {
            $aviso = $this->avisoService->createFromConversation($conversation);

            $conversation->forceFill([
                'aviso_id' => $aviso->id,
            ])->save();

            $this->conversationEventService->record($conversation->refresh(), 'aviso_created', [
                'step_key' => $conversation->currentStepKey(),
                'descripcion' => 'Aviso creado a partir de la conversación',
                'metadata' => [
                    'aviso_id' => $aviso->id,
                    'numero_aviso' => $this->avisoService->displayNumber($aviso),
                    'tipo_ausentismo' => $aviso->tipo_ausentismo,
                ],
            ]);

            return $this->avisoService->buildRegisteredStepResult($aviso);
        }

        if ($action === 'create_aviso_inasistencia') {
            \App\Models\Aviso::create([
                'dni' => $conversation->dni,
                'tipo' => 'inasistencia',
                'fecha_inicio' => now()->toDateString(),
                'cantidad_dias' => $stepResult->payload['cantidad_dias'] ?? null,
                'wa_number' => $conversation->wa_number,
            ]);

            return null;
        }

        if ($action === 'create_aviso_certificado') {
            $this->createLegacyCertificadoAviso($conversation, $stepResult);
        }

        if ($action === 'create_anticipo_certificado_from_conversation') {
            $anticipo = $this->anticipoCertificadoService->createFromConversation($conversation);

            $this->conversationEventService->record($conversation->refresh(), 'anticipo_created', [
                'step_key' => $conversation->currentStepKey(),
                'descripcion' => 'Anticipo de certificado creado a partir de la conversación',
                'metadata' => [
                    'anticipo_certificado_id' => $anticipo->id,
                    'numero_anticipo' => $anticipo->numero_anticipo,
                    'aviso_id' => $anticipo->aviso_id,
                    'tipo_certificado' => $anticipo->tipo_certificado,
                    'cantidad_archivos' => $anticipo->archivos()->count(),
                ],
            ]);

            return $this->anticipoCertificadoService->buildRegisteredStepResult($anticipo);
        }

        return null;
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

    private function logInteractionProcessed(
        Conversacion $conversation,
        ConversationInboundMessage $message,
        StepResult $stepResult,
        ?StepResult $responseResult,
    ): void {
        $context = [
            'conversation_id' => $conversation->id,
            'channel' => $conversation->canal,
            'participant_id' => $message->participantId,
            'incoming_type' => $message->incomingMessageType,
            'current_step' => $conversation->currentStepKey(),
            'next_step' => $stepResult->nextStep,
            'flow_type' => $conversation->tipo_flujo,
            'is_valid' => $stepResult->isValid,
            'error_code' => $stepResult->errorCode,
            'outbound_count' => count($this->buildOutboundMessages($responseResult ?? $stepResult)),
            'should_finish' => $stepResult->shouldFinish,
            'should_cancel' => $stepResult->shouldCancel,
        ];

        Log::info('Conversation interaction processed', $context);

        if (! $stepResult->isValid) {
            Log::warning('Conversation interaction validation failed', $context);
        }
    }

    /**
     * @return array<ConversationOutboundMessage>
     */
    private function buildOutboundMessages(StepResult $stepResult): array
    {
        $messages = [];
        $responseText = $this->messageResolver->resolve($stepResult);

        if ($responseText !== null) {
            $messages[] = ConversationOutboundMessage::text($responseText);
        }

        if ($stepResult->shouldShowMenu) {
            $messages[] = ConversationOutboundMessage::menu($this->mainMenuConfig());
        }

        return $messages;
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
            'body_text' => $this->messageResolver->resolveKey('whatsapp.menu.prompt'),
            'buttons' => $buttons,
        ];
    }

    private function createLegacyCertificadoAviso(Conversacion $conversation, StepResult $stepResult): void
    {
        \App\Models\Aviso::create([
            'dni' => $conversation->dni,
            'tipo' => 'certificado',
            'certificado_base64' => $stepResult->payload['certificado_texto'] ?? null,
            'wa_number' => $conversation->wa_number,
        ]);
    }
}
