<?php

namespace App\Flows\Validators;

use App\Flows\Common\Contracts\Validator;
use App\Flows\Common\ValidationResult;
use App\Models\Conversacion;
use App\Services\Catalogos\ChatCatalogService;

class TipoCertificadoValidator implements Validator
{
    public function validate(Conversacion $conversation, array $input = []): ValidationResult
    {
        $raw = mb_strtolower(trim((string) ($input['text'] ?? '')));
        $buttonId = trim((string) ($input['button_id'] ?? ''));

        if ($raw === '' && $buttonId === '') {
            return ValidationResult::invalid('required');
        }

        $option = app(ChatCatalogService::class)->findOption('tipos_certificado', $input, $conversation);
        if ($option !== null) {
            return ValidationResult::valid([
                'tipo_certificado' => $option->codigo,
                'tipo_certificado_label' => $option->nombre,
            ]);
        }

        return ValidationResult::invalid('invalid_option');
    }
}
