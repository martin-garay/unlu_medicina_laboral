<?php

namespace App\Flows\Placeholders\Handlers;

use App\Flows\Common\AbstractStepHandler;
use App\Flows\Common\StepResult;
use App\Models\Conversacion;
use App\Services\CertificadoMessageService;
use App\Services\Conversation\ConversationContextService;

class CertificadoConfirmacionPendienteStepHandler extends AbstractStepHandler
{
    public function __construct(
        private readonly ConversationContextService $conversationContextService,
        private readonly CertificadoMessageService $certificadoMessageService,
    ) {
    }

    public function stepKey(): string
    {
        return 'certificado_confirmacion_pendiente';
    }

    public function handle(Conversacion $conversation, array $input = []): StepResult
    {
        if ($this->isCancelCommand($input) || $this->isRestartCommand($input)) {
            return $this->success(null, [
                'template' => config('medicina_laboral.mensajes.templates.certificado_cancelacion'),
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

        return $this->success(null, [
            'template' => config('medicina_laboral.mensajes.templates.certificado_resumen_borrador'),
            'template_data' => $this->certificadoMessageService->buildDraftSummaryTemplateData($conversation, [
                'mensaje_estado' => __('whatsapp.certificado.pendiente_confirmacion_siguiente_etapa'),
            ]),
        ]);
    }
}
