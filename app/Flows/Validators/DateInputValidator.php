<?php

namespace App\Flows\Validators;

use App\Flows\Common\Contracts\Validator;
use App\Flows\Common\ValidationResult;
use App\Models\Conversacion;
use Carbon\Carbon;

class DateInputValidator implements Validator
{
    public function validate(Conversacion $conversation, array $input = []): ValidationResult
    {
        $raw = trim((string) ($input['text'] ?? ''));

        if ($raw === '') {
            return ValidationResult::invalid('required');
        }

        $format = (string) config('medicina_laboral.avisos.input_date_format', 'Y-m-d');

        try {
            $date = Carbon::createFromFormat($format, $raw);
        } catch (\Throwable) {
            return ValidationResult::invalid('invalid_date');
        }

        if ($date->format($format) !== $raw) {
            return ValidationResult::invalid('invalid_date');
        }

        return ValidationResult::valid([
            'date' => $date->toDateString(),
        ]);
    }
}
