<?php

namespace App\Flows\Validators;

use App\Flows\Common\Contracts\Validator;
use App\Flows\Common\ValidationResult;
use App\Models\Conversacion;

class RequiredTextValidator implements Validator
{
    public function validate(Conversacion $conversation, array $input = []): ValidationResult
    {
        $text = trim((string) ($input['text'] ?? ''));

        if ($text === '') {
            return ValidationResult::invalid('required');
        }

        return ValidationResult::valid([
            'text' => $text,
        ]);
    }
}
