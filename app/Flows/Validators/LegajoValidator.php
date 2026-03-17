<?php

namespace App\Flows\Validators;

use App\Flows\Common\Contracts\Validator;
use App\Flows\Common\ValidationResult;
use App\Models\Conversacion;

class LegajoValidator implements Validator
{
    public function validate(Conversacion $conversation, array $input = []): ValidationResult
    {
        $raw = trim((string) ($input['text'] ?? ''));

        if ($raw === '') {
            return ValidationResult::invalid('required');
        }

        if (!ctype_digit($raw)) {
            return ValidationResult::invalid('legajo_invalido');
        }

        return ValidationResult::valid([
            'legajo' => $raw,
        ]);
    }
}
