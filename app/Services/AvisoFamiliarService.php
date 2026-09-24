<?php

namespace App\Services;

class AvisoFamiliarService
{
    public function validName(mixed $name): bool
    {
        return is_string($name) && trim($name) !== ''
            && mb_strlen(trim($name)) <= config('medicina_laboral.avisos.nombre_familiar_max_length');
    }

    public function catalog(): array
    {
        return array_map(static fn (string $label): string => __($label), config('medicina_laboral.catalogos.parentescos', []));
    }

    public function missingStep(array $aviso): ?string
    {
        if (($aviso['tipo_ausentismo'] ?? null) !== 'atencion_familiar_enfermo'
            && !($aviso['requiere_datos_familiar'] ?? false)) {
            return null;
        }

        if (!$this->validName($aviso['nombre_familiar'] ?? null)) {
            return 'aviso_nombre_familiar';
        }

        $parentesco = $aviso['parentesco'] ?? null;

        return is_string($parentesco) && array_key_exists($parentesco, $this->catalog())
            ? null : 'aviso_parentesco';
    }

    public function resolveParentesco(array $input): ?string
    {
        if (!in_array($input['incoming_message_type'] ?? 'text', ['text', 'button', 'interactive'], true)) {
            return null;
        }

        $text = mb_strtolower(trim((string) ($input['text'] ?? '')));
        $buttonId = $input['button_id'] ?? null;

        $index = 0;
        foreach ($this->catalog() as $key => $label) {
            $index++;
            if ($buttonId !== null && $buttonId !== '') {
                if ($buttonId === 'parentesco_' . $key) {
                    return $key;
                }
            } elseif (in_array($text, [$key, mb_strtolower($label), (string) $index], true)) {
                return $key;
            }
        }

        return null;
    }

    public function menu(): array
    {
        $buttons = [];
        $lines = [__('whatsapp.aviso.prompts.parentesco')];
        foreach ($this->catalog() as $key => $label) {
            $buttons[] = ['id' => 'parentesco_' . $key, 'title' => $label];
            $lines[] = count($buttons) . '. ' . $label;
        }

        return [
            'type' => 'list',
            'body_text' => implode("\n", $lines),
            'button_text' => __('whatsapp.aviso.seleccionar_parentesco'),
            'buttons' => $buttons,
        ];
    }
}
