<?php

namespace App\Flows\Validators;

use App\Flows\Common\Contracts\Validator;
use App\Flows\Common\ValidationResult;
use App\Models\Conversacion;

class JornadaLaboralValidator implements Validator
{
    public function validate(Conversacion $conversation, array $input = []): ValidationResult
    {
        $text = trim((string) ($input['text'] ?? ''));
        $legacyKey = null;
        if ($text === '' && ($input['button_id'] ?? '') !== '') {
            [$legacyKey, $text] = match ((string) $input['button_id']) {
                'jornada_planta_permanente' => ['planta_permanente', 'Planta permanente'],
                'jornada_parcial' => ['parcial', 'Parcial'],
                default => [null, ''],
            };
        }

        if ($text === '') {
            return ValidationResult::invalid('required');
        }

        if (mb_strlen($text) > 255) {
            return ValidationResult::invalid('max_length');
        }

        return ValidationResult::valid([
            'jornada_laboral' => $legacyKey,
            'jornada_laboral_label' => $text,
            'text' => $text,
        ]);
    }
}
