<?php

namespace App\Flows\Aviso\Handlers;

use App\Flows\Common\AbstractStepHandler;
use App\Flows\Common\StepResult;
use App\Models\Conversacion;
use App\Services\AvisoService;
use App\Services\Conversation\ConversationContextService;

class AvisoObservacionesStepHandler extends AbstractStepHandler
{
    public function __construct(
        private readonly ConversationContextService $conversationContextService,
        private readonly AvisoService $avisoService,
    ) {
    }

    public function stepKey(): string
    {
        return 'aviso_observaciones';
    }

    public function handle(Conversacion $conversation, array $input = []): StepResult
    {
        if ($this->isCancelCommand($input) || $this->isRestartCommand($input)) {
            return $this->returnToMainMenu($conversation);
        }

        $text = trim((string) ($input['text'] ?? ''));

        if ($text === '') {
            return $this->invalid('required', 'whatsapp.errores.required', [
                'increment_attempts' => 1,
            ]);
        }

        $skipValues = array_map('mb_strtolower', config('medicina_laboral.avisos.observaciones_skip_keywords', []));
        $normalized = mb_strtolower($text);
        $observaciones = in_array($normalized, $skipValues, true) ? null : $text;

        $conversationUpdates = $this->conversationContextService->withAvisoData($conversation, [
            'observaciones' => $observaciones,
        ]);

        $avisoData = array_merge($this->conversationContextService->avisoData($conversation), [
            'observaciones' => $observaciones,
        ]);

        if (($avisoData['requiere_datos_familiar'] ?? false) === true) {
            return $this->success('whatsapp.aviso.pendiente_familiar_siguiente_etapa', [
                'next_step' => 'aviso_familiar_pendiente',
                'next_state' => 'aviso_familiar_pendiente',
                'payload' => [
                    'event_name' => 'aviso_partial_flow_completed',
                    'event_description' => 'Tramo inicial del aviso completado',
                    'event_metadata' => [
                        'requires_familiar_subflow' => true,
                    ],
                    'conversation_updates' => $conversationUpdates,
                ],
            ]);
        }

        return $this->success(null, [
            'template' => config('medicina_laboral.mensajes.templates.aviso_confirmacion_final'),
            'template_data' => $this->avisoService->buildConfirmationTemplateData($conversation, [
                'observaciones' => $observaciones,
            ]),
            'next_step' => 'aviso_confirmacion_final',
            'next_state' => 'aviso_confirmacion_final',
            'payload' => [
                'event_name' => 'aviso_ready_for_confirmation',
                'event_description' => 'Aviso listo para confirmación final',
                'event_metadata' => [
                    'requires_familiar_subflow' => false,
                ],
                'conversation_updates' => $conversationUpdates,
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
