<?php

namespace App\Flows\Certificado\Handlers;

use App\Flows\Common\AbstractStepHandler;
use App\Flows\Common\Contracts\Validator;
use App\Flows\Common\StepResult;
use App\Models\Conversacion;
use App\Services\Conversation\ConversationContextService;

class CertificadoNumeroAvisoStepHandler extends AbstractStepHandler
{
    public function __construct(
        private readonly Validator $validator,
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
            return $this->returnToMainMenu($conversation);
        }

        $validation = $this->validator->validate($conversation, $input);

        if (!$validation->isValid) {
            return $this->invalid(
                $validation->errorCode ?? 'aviso_inexistente',
                $this->resolveErrorMessageKey($validation->errorCode),
                ['increment_attempts' => 1]
            );
        }

        return $this->success('whatsapp.certificado.tipo_certificado', [
            'next_step' => 'certificado_tipo',
            'next_state' => 'certificado_tipo',
            'payload' => [
                'event_name' => 'certificado_aviso_validated',
                'event_description' => 'Aviso validado para anticipo de certificado',
                'event_metadata' => [
                    'aviso_id' => $validation->normalized['aviso_id'] ?? null,
                    'numero_aviso' => $validation->normalized['numero_aviso'] ?? null,
                ],
                'conversation_updates' => $this->conversationContextService->withCertificadoData($conversation, [
                    'aviso_id' => $validation->normalized['aviso_id'] ?? null,
                    'numero_aviso' => $validation->normalized['numero_aviso'] ?? null,
                ]),
            ],
        ]);
    }

    private function resolveErrorMessageKey(?string $errorCode): string
    {
        return match ($errorCode) {
            'aviso_no_corresponde_legajo' => 'whatsapp.errores.aviso_no_corresponde_legajo',
            'plazo_vencido_anticipo' => 'whatsapp.errores.plazo_vencido_anticipo',
            'no_open_aviso' => 'whatsapp.errores.no_open_aviso',
            'required' => 'whatsapp.errores.required',
            default => 'whatsapp.errores.aviso_inexistente',
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
