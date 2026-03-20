<?php

namespace App\Flows\Aviso\Handlers;

use App\Flows\Common\AbstractStepHandler;
use App\Flows\Common\Contracts\Validator;
use App\Flows\Common\StepResult;
use App\Models\Conversacion;
use App\Services\Conversation\ConversationContextService;

class AvisoTipoAusentismoStepHandler extends AbstractStepHandler
{
    public function __construct(
        private readonly Validator $validator,
        private readonly ConversationContextService $conversationContextService,
    ) {
    }

    public function stepKey(): string
    {
        return 'aviso_tipo_ausentismo';
    }

    public function handle(Conversacion $conversation, array $input = []): StepResult
    {
        if ($this->isCancelCommand($input) || $this->isRestartCommand($input)) {
            return $this->returnToMainMenu($conversation);
        }

        $validation = $this->validator->validate($conversation, $input);

        if (!$validation->isValid) {
            return $this->invalid($validation->errorCode ?? 'invalid_option', 'whatsapp.errores.invalid_option', [
                'message' => $this->buildInvalidTipoAusentismoMessage(),
                'increment_attempts' => 1,
            ]);
        }

        return $this->success('whatsapp.aviso.prompts.motivo', [
            'next_step' => 'aviso_motivo',
            'next_state' => 'aviso_motivo',
            'payload' => [
                'conversation_updates' => $this->conversationContextService->withAvisoData($conversation, [
                    'tipo_ausentismo' => $validation->normalized['tipo_ausentismo'] ?? null,
                    'tipo_ausentismo_label' => $validation->normalized['tipo_ausentismo_label'] ?? null,
                    'requiere_datos_familiar' => ($validation->normalized['tipo_ausentismo'] ?? null) === 'atencion_familiar_enfermo',
                ]),
            ],
        ]);
    }

    private function buildInvalidTipoAusentismoMessage(): string
    {
        return $this->buildNumberedOptionsMessage(
            'whatsapp.errores.invalid_option',
            config('medicina_laboral.catalogos.tipos_ausentismo', [])
        );
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
