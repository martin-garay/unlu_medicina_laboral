<?php

namespace App\Flows\Certificado\Handlers;

use App\Flows\Common\AbstractStepHandler;
use App\Flows\Common\StepResult;
use App\Models\Conversacion;
use App\Services\Conversation\ConversationContextService;

class CertificadoAdjuntoStepHandler extends AbstractStepHandler
{
    public function __construct(
        private readonly ConversationContextService $conversationContextService,
    ) {
    }

    public function stepKey(): string
    {
        return 'certificado_adjunto';
    }

    public function handle(Conversacion $conversation, array $input = []): StepResult
    {
        if ($this->isCancelCommand($input) || $this->isRestartCommand($input)) {
            return $this->returnToMainMenu($conversation);
        }

        $incomingType = $input['incoming_message_type'] ?? null;
        $media = $input['media'] ?? null;

        if (!$this->isSupportedAttachment($incomingType, $media)) {
            return $this->invalid('invalid_attachment_type', 'whatsapp.certificado.errores.adjunto_requerido', [
                'increment_attempts' => 1,
            ]);
        }

        if (!$this->hasAllowedMimeType($media)) {
            return $this->invalid('invalid_attachment_type', 'whatsapp.errores.invalid_attachment_type', [
                'increment_attempts' => 1,
            ]);
        }

        $currentData = $this->conversationContextService->certificadoData($conversation);
        $attachments = $currentData['adjuntos'] ?? [];

        $attachments[] = [
            'provider_media_id' => $media['provider_media_id'] ?? null,
            'mime_type' => $media['mime_type'] ?? null,
            'filename' => $media['filename'] ?? null,
            'caption' => $media['caption'] ?? null,
            'source_type' => $media['source_type'] ?? $incomingType,
        ];

        return $this->success('whatsapp.certificado.pendiente_confirmacion_siguiente_etapa', [
            'next_step' => 'certificado_confirmacion_pendiente',
            'next_state' => 'certificado_confirmacion_pendiente',
            'payload' => [
                'event_name' => 'certificado_attachment_registered',
                'event_description' => 'Adjunto de certificado registrado en borrador',
                'event_metadata' => [
                    'attachments_count' => count($attachments),
                    'source_type' => $media['source_type'] ?? $incomingType,
                ],
                'conversation_updates' => $this->conversationContextService->withCertificadoData($conversation, [
                    'adjuntos' => $attachments,
                ]),
            ],
        ]);
    }

    private function isSupportedAttachment(?string $incomingType, ?array $media): bool
    {
        if ($media === null) {
            return false;
        }

        return in_array(
            $incomingType,
            config('medicina_laboral.certificados.allowed_incoming_message_types', ['document', 'image']),
            true
        );
    }

    private function hasAllowedMimeType(?array $media): bool
    {
        $mimeType = $media['mime_type'] ?? null;

        if ($mimeType === null) {
            return false;
        }

        return in_array($mimeType, config('medicina_laboral.certificados.allowed_mime_types', []), true);
    }

    private function returnToMainMenu(Conversacion $conversation): StepResult
    {
        return $this->success('whatsapp.cancelacion.volver_menu_principal', [
            'next_step' => 'menu_principal',
            'next_state' => 'menu_principal',
            'should_show_menu' => true,
            'payload' => [
                'event_name' => 'subflow_cancelled_to_menu',
                'event_description' => 'Subflujo cancelado y retorno al menú principal',
                'event_metadata' => [
                    'from_step' => $this->stepKey(),
                    'flow' => $conversation->tipo_flujo,
                ],
                'conversation_updates' => $this->conversationContextService->resetCurrentFlowContext($conversation),
            ],
        ]);
    }
}
