<?php

namespace App\Flows\Certificado\Handlers;

use App\Flows\Common\AbstractStepHandler;
use App\Flows\Common\StepResult;
use App\Models\Conversacion;
use App\Services\Conversation\ConversationContextService;

class CertificadoConfirmacionFinalStepHandler extends AbstractStepHandler
{
    public function __construct(
        private readonly ConversationContextService $conversationContextService,
    ) {
    }

    public function stepKey(): string
    {
        return 'certificado_confirmacion_final';
    }

    public function handle(Conversacion $conversation, array $input = []): StepResult
    {
        if ($this->isCancelCommand($input) || $this->isCancelSelection($input)) {
            return $this->success(null, [
                'template' => config('medicina_laboral.mensajes.templates.certificado_cancelacion'),
                'next_step' => 'menu_principal',
                'next_state' => 'menu_principal',
                'should_show_menu' => true,
                'payload' => [
                    'event_name' => 'subflow_cancelled_to_menu',
                    'event_description' => 'Confirmación final del anticipo cancelada y retorno al menú principal',
                    'event_metadata' => [
                        'from_step' => $this->stepKey(),
                        'flow' => $conversation->tipo_flujo,
                    ],
                    'conversation_updates' => $this->conversationContextService->resetCurrentFlowContext($conversation),
                ],
            ]);
        }

        if ($this->isConfirmSelection($input)) {
            return $this->success(null, [
                'should_finish' => true,
                'payload' => [
                    'business_action' => 'create_anticipo_certificado_from_conversation',
                    'close_reason' => 'completed',
                    'close_attributes' => [
                        'estado' => 'anticipo_registrado',
                        'estado_actual' => 'anticipo_registrado',
                        'paso_actual' => 'anticipo_registrado',
                    ],
                    'event_name' => 'anticipo_confirmation_accepted',
                    'event_step_key' => $this->stepKey(),
                    'event_description' => 'Confirmación final del anticipo aceptada',
                ],
            ]);
        }

        return $this->invalid('invalid_option', 'whatsapp.errores.invalid_option', [
            'increment_attempts' => 1,
        ]);
    }

    private function isConfirmSelection(array $input): bool
    {
        $text = $this->normalizedText($input);

        return in_array($text, [
            '1',
            '1.',
            'confirmar',
            'confirmar anticipo',
            'confirmar anticipo de certificado',
        ], true);
    }

    private function isCancelSelection(array $input): bool
    {
        $text = $this->normalizedText($input);

        return in_array($text, [
            '2',
            '2.',
            'cancelar',
            'cancelar y volver al menu principal',
            'cancelar y volver al menú principal',
        ], true);
    }
}
