<?php

namespace App\Flows\Handlers;

use App\Flows\Common\AbstractStepHandler;
use App\Flows\Common\Contracts\Validator;
use App\Flows\Common\StepResult;
use App\Models\Conversacion;

class MainMenuStepHandler extends AbstractStepHandler
{
    public function __construct(
        private readonly Validator $validator,
    ) {
    }

    public function stepKey(): string
    {
        return 'menu_principal';
    }

    public function handle(Conversacion $conversation, array $input = []): StepResult
    {
        if ($this->isRestartCommand($input) || $this->isCancelCommand($input)) {
            return $this->success('whatsapp.cancelacion.volver_menu_principal', [
                'should_show_menu' => true,
                'payload' => [
                    'event_name' => 'returned_to_main_menu',
                    'event_description' => 'Retorno explícito al menú principal',
                ],
            ]);
        }

        $validation = $this->validator->validate($conversation, $input);

        if ($validation->isValid) {
            return $this->resolveSelection($validation->normalized['selected_option'] ?? null);
        }

        if ($conversation->cantidad_mensajes_recibidos === 0) {
            return StepResult::make(null, [
                'template' => config('medicina_laboral.mensajes.templates.bienvenida'),
                'should_show_menu' => true,
                'payload' => [
                    'event_name' => 'main_menu_presented',
                    'event_description' => 'Menú principal presentado al iniciar conversación',
                ],
            ]);
        }

        return $this->invalid($validation->errorCode ?? 'invalid_option', 'whatsapp.errores.invalid_option', [
            'should_show_menu' => true,
            'increment_attempts' => 1,
        ]);
    }

    private function resolveSelection(?string $selectedOption): StepResult
    {
        return match ($selectedOption) {
            'consultas' => $this->success('whatsapp.menu.consultas_no_disponible', [
                'should_show_menu' => true,
                'payload' => [
                    'event_name' => 'menu_option_selected',
                    'event_step_key' => $this->stepKey(),
                    'event_description' => 'Opción de menú seleccionada',
                    'event_metadata' => [
                        'selected_option' => 'consultas',
                        'available' => false,
                    ],
                ],
            ]),
            'inasistencia' => $this->success('whatsapp.aviso.intro_transicional', [
                'next_step' => 'esperando_cantidad_dias',
                'next_state' => 'esperando_cantidad_dias',
                'payload' => [
                    'event_name' => 'menu_option_selected',
                    'event_step_key' => $this->stepKey(),
                    'event_description' => 'Opción de menú seleccionada',
                    'event_metadata' => [
                        'selected_option' => 'inasistencia',
                    ],
                    'conversation_updates' => [
                        'tipo' => 'inasistencia',
                        'tipo_flujo' => 'inasistencia',
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
                    'event_name' => 'menu_option_selected',
                    'event_step_key' => $this->stepKey(),
                    'event_description' => 'Opción de menú seleccionada',
                    'event_metadata' => [
                        'selected_option' => 'certificado',
                    ],
                    'conversation_updates' => [
                        'tipo' => 'certificado',
                        'tipo_flujo' => 'certificado',
                    ],
                ],
            ]),
            default => $this->invalid('invalid_option', 'whatsapp.errores.invalid_option', [
                'should_show_menu' => true,
                'increment_attempts' => 1,
            ]),
        };
    }
}
