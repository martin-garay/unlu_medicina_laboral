<?php

namespace App\Flows\Validators;

use App\Flows\Common\Contracts\Validator;
use App\Flows\Common\ValidationResult;
use App\Models\Conversacion;
use App\Services\Catalogos\ChatCatalogService;

class AusentismoTypeValidator implements Validator
{
    public function validate(Conversacion $conversation, array $input = []): ValidationResult
    {
        $raw = mb_strtolower(trim((string) ($input['text'] ?? '')));
        $buttonId = trim((string) ($input['button_id'] ?? ''));

        if ($raw === '' && $buttonId === '') {
            return ValidationResult::invalid('required');
        }

        $option = app(ChatCatalogService::class)->findOption('tipos_ausentismo', $input, $conversation);
        if ($option !== null) {
            return ValidationResult::valid([
                'tipo_ausentismo' => $option->codigo,
                'tipo_ausentismo_label' => $option->nombre,
                'requiere_datos_familiar' => (bool) $option->requiere_datos_familiar,
            ]);
        }

        return ValidationResult::invalid('invalid_option');
    }
}
