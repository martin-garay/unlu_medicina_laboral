<?php

namespace App\Flows\Transitional\Handlers;

use App\Flows\Common\AbstractStepHandler;
use App\Flows\Common\Contracts\Validator;
use App\Flows\Common\StepResult;
use App\Models\Conversacion;

class EsperandoCantidadDiasStepHandler extends AbstractStepHandler
{
    public function __construct(
        private readonly Validator $validator,
    ) {
    }

    public function stepKey(): string
    {
        return 'esperando_cantidad_dias';
    }

    public function handle(Conversacion $conversation, array $input = []): StepResult
    {
        $validation = $this->validator->validate($conversation, $input);

        if (!$validation->isValid) {
            $messageKey = match ($validation->errorCode) {
                'required' => 'whatsapp.errores.required',
                'invalid_format' => 'whatsapp.errores.invalid_format',
                default => 'whatsapp.errores.invalid_data',
            };

            return $this->invalid($validation->errorCode ?? 'invalid_data', $messageKey, [
                'increment_attempts' => 1,
            ]);
        }

        return $this->success('whatsapp.aviso.registrado_breve', [
            'should_finish' => true,
            'payload' => [
                'business_action' => 'create_aviso_inasistencia',
                'cantidad_dias' => $validation->normalized['value'] ?? null,
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
