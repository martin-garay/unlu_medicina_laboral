<?php

namespace App\Flows\Placeholders\Handlers;

use App\Flows\Common\AbstractStepHandler;
use App\Flows\Common\StepResult;
use App\Models\Conversacion;
use App\Services\Conversation\ConversationContextService;

class CertificadoNumeroAvisoPlaceholderStepHandler extends AbstractStepHandler
{
    public function __construct(
        private readonly ConversationContextService $conversationContextService,
    ) {
    }

    public function stepKey(): string
    {
        return 'certificado_numero_aviso';
    }

    public function handle(Conversacion $conversation, array $input = []): StepResult
    {
        if ($this->isCancelCommand($input) || $this->isRestartCommand($input)) {
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
                    'conversation_updates' => array_merge(
                        $this->conversationContextService->resetIdentification($conversation),
                        [
                            'tipo' => null,
                            'tipo_flujo' => null,
                        ]
                    ),
                ],
            ]);
        }

        return $this->success('whatsapp.certificado.pendiente_siguiente_etapa');
    }
}
