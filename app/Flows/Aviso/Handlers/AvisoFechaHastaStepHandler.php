<?php

namespace App\Flows\Aviso\Handlers;

use App\Flows\Common\AbstractStepHandler;
use App\Flows\Common\Contracts\Validator;
use App\Flows\Common\StepResult;
use App\Models\Conversacion;
use App\Services\Conversation\ConversationContextService;

class AvisoFechaHastaStepHandler extends AbstractStepHandler
{
    public function __construct(
        private readonly Validator $validator,
        private readonly ConversationContextService $conversationContextService,
    ) {
    }

    public function stepKey(): string
    {
        return 'aviso_fecha_hasta';
    }

    public function handle(Conversacion $conversation, array $input = []): StepResult
    {
        if ($this->isCancelCommand($input) || $this->isRestartCommand($input)) {
            return $this->returnToMainMenu($conversation);
        }

        $validation = $this->validator->validate($conversation, $input);

        if (!$validation->isValid) {
            $messageKey = $validation->errorCode === 'before_start_date'
                ? 'whatsapp.errores.before_start_date'
                : 'whatsapp.errores.invalid_date';

            return $this->invalid($validation->errorCode ?? 'invalid_date', $messageKey, [
                'increment_attempts' => 1,
            ]);
        }

        return StepResult::make(null, [
            'message' => $this->buildTipoAusentismoPrompt(),
            'next_step' => 'aviso_tipo_ausentismo',
            'next_state' => 'aviso_tipo_ausentismo',
            'payload' => [
                'conversation_updates' => $this->conversationContextService->withAvisoData($conversation, [
                    'fecha_hasta' => $validation->normalized['date'] ?? null,
                ]),
            ],
        ]);
    }

    private function buildTipoAusentismoPrompt(): string
    {
        $lines = [__('whatsapp.aviso.prompts.tipo_ausentismo')];

        foreach (array_values(config('medicina_laboral.catalogos.tipos_ausentismo', [])) as $index => $label) {
            $lines[] = ($index + 1) . '. ' . $label;
        }

        return implode("\n", $lines);
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
