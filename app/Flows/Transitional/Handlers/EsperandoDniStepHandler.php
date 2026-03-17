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
                    'conversation_updates' => [
                        'tipo' => null,
                        'tipo_flujo' => null,
                        'dni' => null,
                    ],
                ],
            ]);
        }

        $validation = $this->validator->validate($conversation, $input);

        if (!$validation->isValid) {
            return $this->invalid($validation->errorCode ?? 'required', 'whatsapp.errores.required', [
                'increment_attempts' => 1,
            ]);
        }

        return match ($conversation->tipo_flujo) {
            'inasistencia' => $this->success('whatsapp.aviso.intro_transicional', [
                'next_step' => 'esperando_cantidad_dias',
                'next_state' => 'esperando_cantidad_dias',
                'payload' => [
                    'conversation_updates' => [
                        'dni' => $validation->normalized['text'] ?? null,
                    ],
                ],
            ]),
            'certificado' => $this->success('whatsapp.certificado.intro_transicional', [
                'message_params' => [
                    'max_files' => config('medicina_laboral.certificados.max_files'),
                    'deadline' => config('medicina_laboral.certificados.deadline_business_hours'),
                    'allowed_extensions' => implode(', ', config('medicina_laboral.certificados.allowed_extensions', [])),
                ],
                'next_step' => 'esperando_certificado',
                'next_state' => 'esperando_certificado',
                'payload' => [
                    'conversation_updates' => [
                        'dni' => $validation->normalized['text'] ?? null,
                    ],
                ],
            ]),
            default => $this->success(null, [
                'next_step' => 'esperando_tipo',
                'next_state' => 'esperando_tipo',
                'should_show_menu' => true,
                'payload' => [
                    'conversation_updates' => [
                        'dni' => $validation->normalized['text'] ?? null,
                    ],
                ],
            ]),
        };
    }
}
