<?php

namespace App\Flows\Identification\Handlers;

use App\Flows\Common\AbstractStepHandler;
use App\Flows\Common\Contracts\Validator;
use App\Flows\Common\StepResult;
use App\Models\Conversacion;
use App\Services\Conversation\ConversationContextService;
use App\Services\Mapuche\Contracts\MapucheWorkerProvider;
use Illuminate\Support\Carbon;

class IdentificacionLegajoStepHandler extends AbstractStepHandler
{
    public function __construct(
        private readonly Validator $validator,
        private readonly ConversationContextService $conversationContextService,
        private readonly MapucheWorkerProvider $mapucheWorkerProvider,
    ) {
    }

    public function stepKey(): string
    {
        return 'identificacion_legajo';
    }

    public function handle(Conversacion $conversation, array $input = []): StepResult
    {
        if ($this->isCancelCommand($input) || $this->isRestartCommand($input)) {
            return $this->returnToMainMenu($conversation);
        }

        $validation = $this->validator->validate($conversation, $input);

        if (!$validation->isValid) {
            $messageKey = $validation->errorCode === 'required'
                ? 'whatsapp.errores.required'
                : 'whatsapp.errores.legajo_invalido';

            return $this->invalid($validation->errorCode ?? 'legajo_invalido', $messageKey, [
                'increment_attempts' => 1,
            ]);
        }

        $worker = $this->mapucheWorkerProvider->findByLegajo((string) ($validation->normalized['legajo'] ?? ''));

        if ($worker === null) {
            return $this->invalid('legajo_no_encontrado', 'whatsapp.errores.legajo_no_encontrado', [
                'increment_attempts' => 1,
            ]);
        }

        return StepResult::make(null, [
            'message' => $this->buildSedePrompt(),
            'next_step' => 'identificacion_sede',
            'next_state' => 'identificacion_sede',
            'payload' => [
                'conversation_updates' => $this->conversationContextService->withIdentificationData($conversation, [
                    'legajo' => $validation->normalized['legajo'] ?? null,
                    'mapuche_lookup' => array_merge($worker->toArray(), [
                        'resolved_at' => Carbon::now()->toIso8601String(),
                    ]),
                ]),
            ],
        ]);
    }

    private function buildSedePrompt(): string
    {
        $lines = [__('whatsapp.identificacion.sede')];

        foreach (array_values(config('medicina_laboral.catalogos.sedes', [])) as $index => $label) {
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
