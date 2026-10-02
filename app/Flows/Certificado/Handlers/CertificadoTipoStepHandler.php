<?php

namespace App\Flows\Certificado\Handlers;

use App\Flows\Common\AbstractStepHandler;
use App\Flows\Common\Contracts\Validator;
use App\Flows\Common\StepResult;
use App\Models\Conversacion;
use App\Services\Certificates\CertificateAttachmentService;
use App\Services\Certificates\CertificatePolicy;
use App\Services\Conversation\ConversationContextService;
use Illuminate\Support\Str;

class CertificadoTipoStepHandler extends AbstractStepHandler
{
    public function __construct(
        private readonly Validator $validator,
        private readonly ConversationContextService $conversationContextService,
    ) {}

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

        if (! $validation->isValid) {
            return $this->invalid($validation->errorCode ?? 'invalid_option', 'whatsapp.errores.invalid_option', [
                'menu_config' => $this->configuredMenu('tipos_certificado'),
                'increment_attempts' => 1,
            ]);
        }

        $policy = app(CertificatePolicy::class)->snapshot();
        $realStorage = app(CertificateAttachmentService::class)->enabled();

        return $this->success($realStorage ? 'certificates.attach' : 'whatsapp.certificado.adjuntar_archivo', [
            'message_params' => ['max_files' => $policy['max_files'], 'max_mib' => $policy['max_bytes'] / 1048576,
                'formats' => implode(', ', $policy['extensions'])],
            'next_step' => 'certificado_adjunto',
            'next_state' => 'certificado_adjunto',
            'payload' => [
                'conversation_updates' => $this->conversationContextService->withCertificadoData($conversation, [
                    'tipo_certificado' => $validation->normalized['tipo_certificado'] ?? null,
                    'tipo_certificado_label' => $validation->normalized['tipo_certificado_label'] ?? null,
                    'adjuntos' => [],
                    'intento_uuid' => (string) Str::uuid(),
                    'politica' => $policy,
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
