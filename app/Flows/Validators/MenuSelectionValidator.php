<?php

namespace App\Flows\Validators;

use App\Flows\Common\Contracts\Validator;
use App\Flows\Common\ValidationResult;
use App\Models\Conversacion;

class MenuSelectionValidator implements Validator
{
    public function validate(Conversacion $conversation, array $input = []): ValidationResult
    {
        $buttonId = $input['button_id'] ?? null;
        $text = strtolower(trim((string) ($input['text'] ?? '')));
        $options = config('medicina_laboral.mensajes.current_webhook_menu_options', []);
        $catalog = config('medicina_laboral.catalogos.menu_principal', []);

        foreach ($options as $optionKey) {
            $option = $catalog[$optionKey] ?? null;

            if (!$option) {
                continue;
            }

            if ($buttonId && ($option['id'] ?? null) === $buttonId) {
                return ValidationResult::valid([
                    'selected_option' => $option['legacy_code'] ?? $optionKey,
                ]);
            }

            $aliases = array_map('strtolower', $option['aliases'] ?? []);

            if ($text !== '' && in_array($text, $aliases, true)) {
                return ValidationResult::valid([
                    'selected_option' => $option['legacy_code'] ?? $optionKey,
                ]);
            }
        }

        return ValidationResult::invalid('invalid_option');
    }
}
