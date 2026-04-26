<?php

namespace App\Flows\Certificado\Handlers;

use App\Flows\Common\AbstractStepHandler;
use App\Flows\Common\StepResult;
use App\Models\Conversacion;
use App\Services\CertificadoMessageService;
use App\Services\Conversation\ConversationContextService;

class CertificadoAdjuntarOtroStepHandler extends AbstractStepHandler
{
    public function __construct(
        private readonly ConversationContextService $conversationContextService,
        private readonly CertificadoMessageService $certificadoMessageService,
    ) {
    }

    public function stepKey(): string
    {
        return 'certificado_adjuntar_otro';
    }

    public function handle(Conversacion $conversation, array $input = []): StepResult
    {
        if ($this->isCancelCommand($input) || $this->isRestartCommand($input)) {
            return $this->returnToMainMenu($conversation);
        }

        if ($this->matchesAttachMoreKeywords($input, 'attach_more_yes_keywords')) {
            return $this->continueWithAttachment($conversation);
        }

        if ($this->matchesAttachMoreKeywords($input, 'attach_more_no_keywords')) {
            return $this->continueToConfirmation($conversation);
        }

        return $this->invalid('invalid_option', null, [
            'message' => implode("\n", [
                __('whatsapp.errores.invalid_option'),
                $this->buildAttachMorePrompt(),
            ]),
            'increment_attempts' => 1,
        ]);
    }

    private function continueWithAttachment(Conversacion $conversation): StepResult
    {
        $attachments = $this->attachments($conversation);

        if (count($attachments) >= $this->maxFiles()) {
            return $this->continueToConfirmation($conversation);
        }

        return $this->success('whatsapp.certificado.adjuntar_archivo', [
            'next_step' => 'certificado_adjunto',
            'next_state' => 'certificado_adjunto',
            'payload' => [
                'event_name' => 'certificado_attach_more_selected',
                'event_description' => 'Usuario eligió adjuntar otro archivo al anticipo',
                'event_metadata' => [
                    'attachments_count' => count($attachments),
                    'max_files' => $this->maxFiles(),
                ],
            ],
        ]);
    }

    private function continueToConfirmation(Conversacion $conversation): StepResult
    {
        $attachments = $this->attachments($conversation);

        if ($attachments === []) {
            return $this->success('whatsapp.certificado.errores.adjunto_requerido', [
                'next_step' => 'certificado_adjunto',
                'next_state' => 'certificado_adjunto',
                'payload' => [
                    'event_name' => 'certificado_attachment_missing_before_confirmation',
                    'event_description' => 'Intento de confirmar anticipo sin adjuntos',
                ],
            ]);
        }

        return $this->success(null, [
            'template' => config('medicina_laboral.mensajes.templates.certificado_confirmacion_final'),
            'template_data' => $this->certificadoMessageService->buildConfirmationTemplateData($conversation),
            'next_step' => 'certificado_confirmacion_final',
            'next_state' => 'certificado_confirmacion_final',
            'payload' => [
                'event_name' => 'certificado_attachment_selection_completed',
                'event_description' => 'Selección de adjuntos del anticipo completada',
                'event_metadata' => [
                    'attachments_count' => count($attachments),
                    'max_files' => $this->maxFiles(),
                ],
            ],
        ]);
    }

    private function attachments(Conversacion $conversation): array
    {
        $currentData = $this->conversationContextService->certificadoData($conversation);

        return $currentData['adjuntos'] ?? [];
    }

    private function buildAttachMorePrompt(): string
    {
        return implode("\n", [
            __('whatsapp.certificado.adjuntar_otro_archivo'),
            '1. ' . __('whatsapp.certificado.options.si'),
            '2. ' . __('whatsapp.certificado.options.no_continuar'),
        ]);
    }

    private function matchesAttachMoreKeywords(array $input, string $configKey): bool
    {
        $text = $this->normalizedText($input);

        if ($text === '') {
            return false;
        }

        $keywords = array_map(
            static fn (string $value): string => mb_strtolower(trim($value)),
            config('medicina_laboral.certificados.' . $configKey, [])
        );

        return in_array($text, $keywords, true);
    }

    private function maxFiles(): int
    {
        return (int) config('medicina_laboral.certificados.max_files', 3);
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
