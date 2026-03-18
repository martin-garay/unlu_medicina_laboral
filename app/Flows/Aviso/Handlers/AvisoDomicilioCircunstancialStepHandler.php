<?php

namespace App\Flows\Aviso\Handlers;

use App\Flows\Common\AbstractStepHandler;
use App\Flows\Common\StepResult;
use App\Models\Conversacion;
use App\Services\Conversation\ConversationContextService;

class AvisoDomicilioCircunstancialStepHandler extends AbstractStepHandler
{
    public function __construct(
        private readonly ConversationContextService $conversationContextService,
    ) {
    }

    public function stepKey(): string
    {
        return 'aviso_domicilio_circunstancial';
    }

    public function handle(Conversacion $conversation, array $input = []): StepResult
    {
        if ($this->isCancelCommand($input) || $this->isRestartCommand($input)) {
            return $this->returnToMainMenu($conversation);
        }

        $decision = $this->normalizeDecision($input);

        if ($decision === null) {
            return StepResult::invalid('invalid_option', 'whatsapp.errores.invalid_option', [
                'message' => $this->buildPromptWithError(),
                'increment_attempts' => 1,
            ]);
        }

        if ($decision === true) {
            return $this->success('whatsapp.aviso.prompts.domicilio_circunstancial', [
                'next_step' => 'aviso_domicilio_circunstancial_detalle',
                'next_state' => 'aviso_domicilio_circunstancial_detalle',
                'payload' => [
                    'conversation_updates' => $this->conversationContextService->withAvisoData($conversation, [
                        'informo_domicilio_circunstancial' => true,
                    ]),
                ],
            ]);
        }

        return $this->success('whatsapp.aviso.prompts.observaciones_pregunta', [
            'next_step' => 'aviso_observaciones',
            'next_state' => 'aviso_observaciones',
            'payload' => [
                'conversation_updates' => $this->conversationContextService->withAvisoData($conversation, [
                    'informo_domicilio_circunstancial' => false,
                    'domicilio_circunstancial' => null,
                ]),
            ],
        ]);
    }

    private function normalizeDecision(array $input): ?bool
    {
        $text = $this->normalizedText($input);
        $yes = array_map('mb_strtolower', config('medicina_laboral.avisos.domicilio_yes_keywords', []));
        $no = array_map('mb_strtolower', config('medicina_laboral.avisos.domicilio_no_keywords', []));

        if (in_array($text, $yes, true)) {
            return true;
        }

        if (in_array($text, $no, true)) {
            return false;
        }

        return null;
    }

    private function buildPromptWithError(): string
    {
        return implode("\n", [
            __('whatsapp.errores.invalid_option'),
            '1. ' . __('whatsapp.aviso.options.si'),
            '2. ' . __('whatsapp.aviso.options.no_continuar'),
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
