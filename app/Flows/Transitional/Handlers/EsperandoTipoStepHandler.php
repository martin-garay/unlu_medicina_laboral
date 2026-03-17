<?php

namespace App\Flows\Transitional\Handlers;

use App\Flows\Common\AbstractStepHandler;
use App\Flows\Common\Contracts\Validator;
use App\Flows\Common\StepResult;
use App\Models\Conversacion;

class EsperandoTipoStepHandler extends AbstractStepHandler
{
    public function __construct(
        private readonly Validator $validator,
    ) {
    }

    public function stepKey(): string
    {
        return 'esperando_tipo';
    }

    public function handle(Conversacion $conversation, array $input = []): StepResult
    {
        $validation = $this->validator->validate($conversation, $input);

        if (!$validation->isValid) {
            return $this->invalid($validation->errorCode ?? 'invalid_option', null, [
                'should_show_menu' => true,
                'increment_attempts' => 1,
                'payload' => $validation->normalized,
            ]);
        }

        $selectedOption = $validation->normalized['selected_option'] ?? null;

        return match ($selectedOption) {
            'inasistencia' => $this->success('whatsapp.aviso.prompts.cantidad_dias_legacy', [
                'next_step' => 'esperando_cantidad_dias',
                'next_state' => 'esperando_cantidad_dias',
                'payload' => ['selected_option' => $selectedOption],
            ]),
            'certificado' => $this->success('whatsapp.certificado.detalle_o_adjunto_legacy', [
                'next_step' => 'esperando_certificado',
                'next_state' => 'esperando_certificado',
                'payload' => ['selected_option' => $selectedOption],
            ]),
            default => $this->invalid('invalid_option', null, [
                'should_show_menu' => true,
                'increment_attempts' => 1,
                'payload' => ['selected_option' => $selectedOption],
            ]),
        };
    }
}
