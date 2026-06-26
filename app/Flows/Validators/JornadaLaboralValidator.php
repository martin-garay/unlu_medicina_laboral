<?php

namespace App\Flows\Validators;

use App\Flows\Common\Contracts\Validator;
use App\Flows\Common\ValidationResult;
use App\Models\Conversacion;

class JornadaLaboralValidator implements Validator
{
    public function validate(Conversacion $conversation, array $input = []): ValidationResult
    {
        $raw = mb_strtolower(trim((string) ($input['text'] ?? '')));
        $buttonId = trim((string) ($input['button_id'] ?? ''));

        if ($raw === '' && $buttonId === '') {
            return ValidationResult::invalid('required');
        }

        $catalog = config('medicina_laboral.catalogos.jornadas_laborales', []);
        $buttons = config('medicina_laboral.mensajes.menus.jornadas_laborales.buttons', []);
        $keys = array_keys($catalog);

        foreach ($keys as $index => $key) {
            $label = mb_strtolower((string) ($catalog[$key] ?? ''));
            $numericAlias = (string) ($index + 1);
            $configuredButtonId = (string) ($buttons[$index]['id'] ?? '');

            if (
                $buttonId === $configuredButtonId
                || $raw === mb_strtolower($key)
                || $raw === $label
                || $raw === $numericAlias
            ) {
                return ValidationResult::valid([
                    'jornada_laboral' => $key,
                    'jornada_laboral_label' => $catalog[$key],
                ]);
            }
        }

        return ValidationResult::invalid('invalid_option');
    }
}
