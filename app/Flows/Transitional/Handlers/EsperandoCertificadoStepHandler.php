<?php

namespace App\Flows\Transitional\Handlers;

use App\Flows\Common\AbstractStepHandler;
use App\Flows\Common\Contracts\Validator;
use App\Flows\Common\StepResult;
use App\Models\Conversacion;

class EsperandoCertificadoStepHandler extends AbstractStepHandler
{
    public function __construct(
        private readonly Validator $validator,
    ) {
    }

    public function stepKey(): string
    {
        return 'esperando_certificado';
    }

    public function handle(Conversacion $conversation, array $input = []): StepResult
    {
        $validation = $this->validator->validate($conversation, $input);

        if (!$validation->isValid) {
            return $this->invalid($validation->errorCode ?? 'required', 'whatsapp.errores.required', [
                'increment_attempts' => 1,
            ]);
        }

        return $this->success('whatsapp.certificado.registrado_breve', [
            'should_finish' => true,
            'payload' => [
                'business_action' => 'create_aviso_certificado',
                'certificado_texto' => $validation->normalized['text'] ?? null,
                'close_reason' => 'completed',
                'close_attributes' => [
                    'estado' => 'completada',
                    'estado_actual' => 'completada',
                    'paso_actual' => 'completada',
                ],
            ],
        ]);
    }
}
