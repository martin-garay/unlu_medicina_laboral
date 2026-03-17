<?php

namespace App\Flows\Transitional\Handlers;

use App\Flows\Common\AbstractStepHandler;
use App\Flows\Common\Contracts\Validator;
use App\Flows\Common\StepResult;
use App\Models\Conversacion;

class EsperandoDniStepHandler extends AbstractStepHandler
{
    public function __construct(
        private readonly Validator $validator,
    ) {
    }

    public function stepKey(): string
    {
        return 'esperando_dni';
    }

    public function handle(Conversacion $conversation, array $input = []): StepResult
    {
        $validation = $this->validator->validate($conversation, $input);

        if (!$validation->isValid) {
            return $this->invalid($validation->errorCode ?? 'required', 'whatsapp.errores.required', [
                'increment_attempts' => 1,
            ]);
        }

        return $this->success(null, [
            'next_step' => 'esperando_tipo',
            'next_state' => 'esperando_tipo',
            'should_show_menu' => true,
            'payload' => [
                'conversation_updates' => [
                    'dni' => $validation->normalized['text'] ?? null,
                ],
            ],
        ]);
    }
}
