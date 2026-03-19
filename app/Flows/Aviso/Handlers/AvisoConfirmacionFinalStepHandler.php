<?php

namespace App\Flows\Aviso\Handlers;

use App\Flows\Common\AbstractStepHandler;
use App\Flows\Common\StepResult;
use App\Models\Conversacion;
use App\Services\Conversation\ConversationContextService;

class AvisoConfirmacionFinalStepHandler extends AbstractStepHandler
{
    public function __construct(
        private readonly ConversationContextService $conversationContextService,
    ) {
    }

    public function stepKey(): string
    {
        return 'aviso_confirmacion_final';
    }

    public function handle(Conversacion $conversation, array $input = []): StepResult
    {
        if ($this->isCancelCommand($input) || $this->isCancelSelection($input)) {
            return $this->success(null, [
                'template' => config('medicina_laboral.mensajes.templates.aviso_cancelacion'),
                'next_step' => 'menu_principal',
                'next_state' => 'menu_principal',
                'should_show_menu' => true,
                'payload' => [
                    'event_name' => 'subflow_cancelled_to_menu',
                    'event_description' => 'Confirmación final cancelada y retorno al menú principal',
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
                    'business_action' => 'create_aviso_from_conversation',
                    'close_reason' => 'completed',
                    'close_attributes' => [
                        'estado' => 'aviso_registrado',
                        'estado_actual' => 'aviso_registrado',
                        'paso_actual' => 'aviso_registrado',
                    ],
                    'event_name' => 'aviso_confirmation_accepted',
                    'event_step_key' => $this->stepKey(),
                    'event_description' => 'Confirmación final del aviso aceptada',
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
            'confirmar aviso',
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
