<?php

namespace App\Flows\Aviso\Handlers;

use App\Flows\Common\AbstractStepHandler;
use App\Flows\Common\StepResult;
use App\Models\Conversacion;
use App\Services\AvisoFamiliarService;
use App\Services\AvisoService;
use App\Services\Conversation\ConversationContextService;

class AvisoParentescoStepHandler extends AbstractStepHandler
{
    public function __construct(
        private readonly AvisoFamiliarService $familiarService,
        private readonly AvisoService $avisoService,
        private readonly ConversationContextService $conversationContextService,
    ) {}

    public function stepKey(): string
    {
        return 'aviso_parentesco';
    }

    public function handle(Conversacion $conversation, array $input = []): StepResult
    {
        if ($this->isCancelCommand($input) || $this->isRestartCommand($input)) {
            return $this->returnToMainMenu($conversation);
        }

        $parentesco = $this->familiarService->resolveParentesco($input, $conversation);
        if ($parentesco === null) {
            return $this->invalid('invalid_option', 'whatsapp.errores.invalid_option', [
                'increment_attempts' => 1,
                'menu_config' => $this->familiarService->menu(),
            ]);
        }

        $result = $this->avisoService->buildConfirmationStepResult($conversation, ['parentesco' => $parentesco]);

        return StepResult::make(null, [
            'template' => $result->template,
            'template_data' => $result->templateData,
            'next_step' => 'aviso_confirmacion_final',
            'next_state' => 'aviso_confirmacion_final',
            'payload' => [
                'conversation_updates' => $this->conversationContextService->withAvisoData($conversation, [
                    'parentesco' => $parentesco,
                    'parentesco_label' => $this->familiarService->label($parentesco, $conversation),
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
