<?php

namespace App\Flows\Identification\Handlers;

use App\Flows\Common\AbstractStepHandler;
use App\Flows\Common\Contracts\Validator;
use App\Flows\Common\StepResult;
use App\Models\Conversacion;
use App\Services\Conversation\ConversationContextService;

class IdentificacionSedeStepHandler extends AbstractStepHandler
{
    public function __construct(
        private readonly Validator $validator,
        private readonly ConversationContextService $conversationContextService,
    ) {
    }

    public function stepKey(): string
    {
        return 'identificacion_sede';
    }

    public function handle(Conversacion $conversation, array $input = []): StepResult
    {
        if ($this->isCancelCommand($input) || $this->isRestartCommand($input)) {
            return $this->returnToMainMenu($conversation);
        }

        $validation = $this->validator->validate($conversation, $input);

        if (!$validation->isValid) {
            return $this->invalid($validation->errorCode ?? 'sede_invalida', 'whatsapp.errores.sede_invalida', [
                'message' => $this->buildInvalidSedeMessage(),
                'increment_attempts' => 1,
            ]);
        }

        return $this->success('whatsapp.identificacion.jornada_laboral', [
            'next_step' => 'identificacion_jornada',
            'next_state' => 'identificacion_jornada',
            'payload' => [
                'conversation_updates' => $this->conversationContextService->withIdentificationData($conversation, [
                    'sede' => $validation->normalized['sede_label'] ?? null,
                    'sede_key' => $validation->normalized['sede_key'] ?? null,
                ]),
            ],
        ]);
    }

    private function buildInvalidSedeMessage(): string
    {
        return $this->buildNumberedOptionsMessage(
            'whatsapp.errores.sede_invalida',
            config('medicina_laboral.catalogos.sedes', [])
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
