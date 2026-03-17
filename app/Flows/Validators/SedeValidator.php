<?php

namespace App\Flows\Validators;

use App\Flows\Common\Contracts\Validator;
use App\Flows\Common\ValidationResult;
use App\Models\Conversacion;

class SedeValidator implements Validator
{
    public function validate(Conversacion $conversation, array $input = []): ValidationResult
    {
        $raw = mb_strtolower(trim((string) ($input['text'] ?? '')));

        if ($raw === '') {
            return ValidationResult::invalid('required');
        }

        $sedes = config('medicina_laboral.catalogos.sedes', []);
        $keys = array_keys($sedes);

        foreach ($keys as $index => $key) {
            $label = mb_strtolower((string) ($sedes[$key] ?? ''));
            $numericAlias = (string) ($index + 1);

            if ($raw === mb_strtolower($key) || $raw === $label || $raw === $numericAlias) {
                return ValidationResult::valid([
                    'sede_key' => $key,
                    'sede_label' => $sedes[$key],
                ]);
            }
        }

        return ValidationResult::invalid('sede_invalida');
    }
}
