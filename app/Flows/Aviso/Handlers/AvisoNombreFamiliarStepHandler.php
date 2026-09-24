<?php

namespace App\Flows\Aviso\Handlers;

use App\Flows\Common\AbstractStepHandler;
use App\Services\AvisoFamiliarService;
use App\Flows\Common\StepResult;
use App\Models\Conversacion;
use App\Services\Conversation\ConversationContextService;

class AvisoNombreFamiliarStepHandler extends AbstractStepHandler
{
    public function __construct(
        private readonly AvisoFamiliarService $familiarService,
        private readonly ConversationContextService $conversationContextService,
    ) {
    }

    public function stepKey(): string
    {
        return 'aviso_nombre_familiar';
    }

    public function supports(Conversacion $conversation): bool
    {
        return parent::supports($conversation) || $conversation->currentStepKey() === 'aviso_familiar_pendiente';
    }

    public function handle(Conversacion $conversation, array $input = []): StepResult
    {
        if ($this->isCancelCommand($input) || $this->isRestartCommand($input)) {
            return $this->returnToMainMenu($conversation);
        }

        // Resume old conversations by asking explicitly; do not treat an arbitrary
        // response to the former placeholder as the family member's name.
        if ($conversation->currentStepKey() === 'aviso_familiar_pendiente') {
            return $this->success('whatsapp.aviso.prompts.nombre_familiar', [
                'next_step' => $this->stepKey(),
                'next_state' => $this->stepKey(),
            ]);
        }

        $name = $input['text'] ?? null;
        if (($input['incoming_message_type'] ?? 'text') !== 'text'
            || !$this->familiarService->validName($name)) {
            return $this->invalid('invalid_family_name', 'whatsapp.aviso.nombre_familiar_invalido', [
                'message_params' => ['max' => config('medicina_laboral.avisos.nombre_familiar_max_length')],
                'increment_attempts' => 1,
            ]);
        }

        return StepResult::make(null, [
            'menu_config' => $this->familiarService->menu(),
            'next_step' => 'aviso_parentesco',
            'next_state' => 'aviso_parentesco',
            'payload' => [
                'conversation_updates' => $this->conversationContextService->withAvisoData($conversation, [
                    'nombre_familiar' => trim($name),
                ]),
            ],
        ]);
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
