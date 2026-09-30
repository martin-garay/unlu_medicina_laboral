<?php

namespace App\Services;

use App\Models\Conversacion;
use App\Services\Catalogos\ChatCatalogService;

class AvisoFamiliarService
{
    public function __construct(private readonly ChatCatalogService $catalogService) {}

    public function validName(mixed $name): bool
    {
        return is_string($name) && trim($name) !== ''
            && mb_strlen(trim($name)) <= config('medicina_laboral.avisos.nombre_familiar_max_length');
    }

    public function catalog(): array
    {
        return $this->catalogService->allOptions('parentescos');
    }

    public function activeCatalog(): array
    {
        return $this->catalogService->options('parentescos');
    }

    public function missingStep(array $aviso): ?string
    {
        if (! ($aviso['requiere_datos_familiar'] ?? false)
            && ($aviso['tipo_ausentismo'] ?? null) !== 'atencion_familiar_enfermo') {
            return null;
        }

        if (! $this->validName($aviso['nombre_familiar'] ?? null)) {
            return 'aviso_nombre_familiar';
        }

        $parentesco = $aviso['parentesco'] ?? null;

        return is_string($parentesco) && array_key_exists($parentesco, $this->catalog())
            ? null : 'aviso_parentesco';
    }

    public function resolveParentesco(array $input, ?Conversacion $conversation = null): ?string
    {
        if (! in_array($input['incoming_message_type'] ?? 'text', ['text', 'button', 'interactive'], true)) {
            return null;
        }

        $text = mb_strtolower(trim((string) ($input['text'] ?? '')));
        $buttonId = $input['button_id'] ?? null;

        $option = $this->catalogService->findOption('parentescos', ['text' => $text, 'button_id' => $buttonId], $conversation);

        return $option?->codigo;
    }

    public function label(string $code, ?Conversacion $conversation = null): ?string
    {
        $snapshot = data_get($conversation?->metadata ?? [], 'catalog_menu_snapshot.parentescos', []);
        foreach ($snapshot as $option) {
            if (($option['codigo'] ?? null) === $code) {
                return $option['nombre'];
            }
        }

        return $this->catalogService->label('parentescos', $code);
    }

    public function menu(): array
    {
        return $this->catalogService->menu('parentescos');
    }
}
