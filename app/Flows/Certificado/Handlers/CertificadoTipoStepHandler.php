<?php

namespace App\Flows\Certificado\Handlers;

use App\Flows\Common\AbstractStepHandler;
use App\Flows\Common\Contracts\Validator;
use App\Flows\Common\StepResult;
use App\Models\Conversacion;
use App\Services\Conversation\ConversationContextService;

class CertificadoTipoStepHandler extends AbstractStepHandler
{
    public function __construct(
        private readonly Validator $validator,
        private readonly ConversationContextService $conversationContextService,
    ) {
    }

    public function stepKey(): string
    {
        return 'certificado_tipo';
    }

    public function handle(Conversacion $conversation, array $input = []): StepResult
    {
        if ($this->isCancelCommand($input) || $this->isRestartCommand($input)) {
            return $this->returnToMainMenu($conversation);
        }

        $validation = $this->validator->validate($conversation, $input);

        if (!$validation->isValid) {
            return $this->invalid($validation->errorCode ?? 'invalid_option', 'whatsapp.errores.invalid_option', [
                'message' => $this->buildInvalidTipoCertificadoMessage(),
                'increment_attempts' => 1,
            ]);
        }

        return $this->success('whatsapp.certificado.adjuntar_archivo', [
            'next_step' => 'certificado_adjunto',
            'next_state' => 'certificado_adjunto',
            'payload' => [
                'conversation_updates' => $this->conversationContextService->withCertificadoData($conversation, [
                    'tipo_certificado' => $validation->normalized['tipo_certificado'] ?? null,
                    'tipo_certificado_label' => $validation->normalized['tipo_certificado_label'] ?? null,
                    'adjuntos' => [],
                ]),
            ],
        ]);
    }

    private function buildInvalidTipoCertificadoMessage(): string
    {
        $lines = [__('whatsapp.errores.invalid_option')];

        foreach (array_values(config('medicina_laboral.catalogos.tipos_certificado', [])) as $index => $label) {
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
