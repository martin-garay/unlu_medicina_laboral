<?php

namespace App\Flows\Validators;

use App\Flows\Common\Contracts\Validator;
use App\Flows\Common\ValidationResult;
use App\Models\Conversacion;

class TipoCertificadoValidator implements Validator
{
    public function validate(Conversacion $conversation, array $input = []): ValidationResult
    {
        $raw = mb_strtolower(trim((string) ($input['text'] ?? '')));

        if ($raw === '') {
            return ValidationResult::invalid('required');
        }

        $catalog = config('medicina_laboral.catalogos.tipos_certificado', []);
        $keys = array_keys($catalog);

        foreach ($keys as $index => $key) {
            $label = mb_strtolower((string) ($catalog[$key] ?? ''));
            $numericAlias = (string) ($index + 1);

            if ($raw === mb_strtolower($key) || $raw === $label || $raw === $numericAlias) {
                return ValidationResult::valid([
                    'tipo_certificado' => $key,
                    'tipo_certificado_label' => $catalog[$key],
                ]);
            }
        }

        return ValidationResult::invalid('invalid_option');
    }
}
