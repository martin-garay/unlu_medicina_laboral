<?php

namespace App\Flows\Identification\Handlers;

use App\Flows\Common\AbstractStepHandler;
use App\Flows\Common\Contracts\Validator;
use App\Flows\Common\StepResult;
use App\Models\Conversacion;
use App\Services\Conversation\ConversationContextService;

class IdentificacionJornadaStepHandler extends AbstractStepHandler
{
    public function __construct(
        private readonly Validator $validator,
        private readonly ConversationContextService $conversationContextService,
    ) {
    }

    public function stepKey(): string
    {
        return 'identificacion_jornada';
    }

    public function handle(Conversacion $conversation, array $input = []): StepResult
    {
        if ($this->isCancelCommand($input) || $this->isRestartCommand($input)) {
            return $this->returnToMainMenu($conversation);
        }

        $validation = $this->validator->validate($conversation, $input);

        if (!$validation->isValid) {
            return $this->invalid($validation->errorCode ?? 'required', 'whatsapp.errores.required', [
                'increment_attempts' => 1,
            ]);
        }

        $conversationUpdates = $this->conversationContextService->withIdentificationData($conversation, [
            'jornada_laboral' => $validation->normalized['text'] ?? null,
        ]);

        return match ($conversation->tipo_flujo) {
            'inasistencia' => $this->success('whatsapp.identificacion.continuacion_aviso', [
                'next_step' => 'aviso_fecha_desde',
                'next_state' => 'aviso_fecha_desde',
                'payload' => [
                    'event_name' => 'identificacion_completed',
                    'event_description' => 'Identificación común completada',
                    'event_metadata' => [
                        'flow' => $conversation->tipo_flujo,
                    ],
                    'conversation_updates' => $conversationUpdates,
                ],
            ]),
            'certificado' => $this->success('whatsapp.identificacion.continuacion_certificado', [
                'next_step' => 'certificado_numero_aviso',
                'next_state' => 'certificado_numero_aviso',
                'payload' => [
                    'event_name' => 'identificacion_completed',
                    'event_description' => 'Identificación común completada',
                    'event_metadata' => [
                        'flow' => $conversation->tipo_flujo,
                    ],
                    'conversation_updates' => $conversationUpdates,
                ],
            ]),
            default => $this->success('whatsapp.general.reinicio', [
                'next_step' => 'menu_principal',
                'next_state' => 'menu_principal',
                'should_show_menu' => true,
                'payload' => [
                    'conversation_updates' => array_merge($conversationUpdates, [
                        'tipo' => null,
                        'tipo_flujo' => null,
                    ]),
                ],
            ]),
        };
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
