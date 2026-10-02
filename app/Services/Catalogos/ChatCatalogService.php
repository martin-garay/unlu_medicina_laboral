<?php

namespace App\Services\Catalogos;

use App\Models\Conversacion;
use App\Models\Parentesco;
use App\Models\Sede;
use App\Models\TipoAusentismo;
use App\Models\TipoCertificado;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ChatCatalogService
{
    private const CATALOGS = [
        'sedes' => [Sede::class, 'sede', 'whatsapp.identificacion.sede', 'whatsapp.catalogos.abrir_sedes'],
        'tipos_ausentismo' => [TipoAusentismo::class, 'ausentismo', 'whatsapp.aviso.prompts.tipo_ausentismo', 'whatsapp.catalogos.abrir_ausentismos'],
        'tipos_certificado' => [TipoCertificado::class, 'certificado', 'whatsapp.certificado.tipo_certificado', 'whatsapp.catalogos.abrir_certificados'],
        'parentescos' => [Parentesco::class, 'parentesco', 'whatsapp.aviso.prompts.parentesco', 'whatsapp.aviso.seleccionar_parentesco'],
    ];

    /** @return Builder<Model> */
    public function query(string $catalog): Builder
    {
        [$model] = $this->definition($catalog);

        return $model::query()->where('activo', true)->orderBy('orden')->orderBy('id');
    }

    /** @return array<string, string> */
    public function options(string $catalog): array
    {
        return $this->query($catalog)->pluck('nombre', 'codigo')->all();
    }

    /** @return array<string, string> */
    public function allOptions(string $catalog): array
    {
        [$model] = $this->definition($catalog);

        return $model::query()->orderBy('orden')->orderBy('id')->pluck('nombre', 'codigo')->all();
    }

    public function findOption(string $catalog, array $input, ?Conversacion $conversation = null): ?object
    {
        [, $prefix] = $this->definition($catalog);
        $raw = mb_strtolower(trim((string) ($input['text'] ?? '')));
        $buttonId = trim((string) ($input['button_id'] ?? ''));
        $snapshot = data_get($conversation?->metadata ?? [], "catalog_menu_snapshot.{$catalog}");
        $options = is_array($snapshot)
            ? $snapshot
            : $this->query($catalog)->get()->map(static fn (Model $record): array => [
                'codigo' => $record->codigo,
                'nombre' => $record->nombre,
                'button_id' => null,
                'requiere_datos_familiar' => (bool) ($record->requiere_datos_familiar ?? false),
            ])->all();

        foreach ($options as $index => $option) {
            $code = is_array($option) ? $option['codigo'] : $option->codigo;
            $name = is_array($option) ? $option['nombre'] : $option->nombre;
            $savedButton = is_array($option) ? ($option['button_id'] ?? null) : null;
            $candidateIds = [$savedButton ?? $this->buttonId($catalog, $prefix, $code), $prefix.'_'.$code];
            if ($catalog === 'tipos_ausentismo' && $code === 'atencion_familiar_enfermo') {
                $candidateIds[] = 'ausentismo_familiar_enfermo'; // ID emitido por versiones previas.
            }

            $legacyLabels = $catalog === 'tipos_ausentismo' && $code === 'atencion_familiar_enfermo'
                ? ['por atención de familiar enfermo']
                : [];
            if (
                ($buttonId !== '' && in_array($buttonId, $candidateIds, true))
                || ($buttonId === '' && in_array($raw, [
                    mb_strtolower($code),
                    mb_strtolower($name),
                    (string) ($index + 1),
                    ...$legacyLabels,
                ], true))
            ) {
                return is_array($option) ? (object) $option : $option;
            }
        }

        return null;
    }

    public function menu(string $catalog): array
    {
        [$model, $prefix, $bodyKey, $buttonKey] = $this->definition($catalog);
        $options = $this->query($catalog)->get();
        $snapshot = [];
        $buttons = $options->map(function (Model $option) use ($prefix, $catalog, &$snapshot): array {
            $buttonId = $this->buttonId($catalog, $prefix, $option->codigo);
            $snapshot[] = [
                'codigo' => $option->codigo,
                'nombre' => $option->nombre,
                'button_id' => $buttonId,
                'requiere_datos_familiar' => (bool) ($option->requiere_datos_familiar ?? false),
            ];

            return [
                'id' => $buttonId,
                'title' => $option->nombre,
            ];
        })->all();

        $bodyText = __($bodyKey);
        $isTextFallback = count($buttons) > 10;
        $isList = ! $isTextFallback && ($catalog === 'parentescos' || count($buttons) > 3);
        if ($isTextFallback) {
            $bodyText .= "\n\n".collect($buttons)
                ->values()
                ->map(static fn (array $button, int $index): string => ($index + 1).'. '.$button['title'])
                ->implode("\n");
        }
        if ($isList) {
            $buttons = array_map(static fn (array $button): array => [
                'id' => $button['id'],
                'title' => $button['title'],
                'description' => '',
            ], $buttons);
        }

        if ($isTextFallback) {
            $buttons = [];
        }

        return [
            'type' => $isTextFallback ? 'text' : ($isList ? 'list' : 'button'),
            'body_text' => $bodyText,
            'button_text' => __($buttonKey),
            'buttons' => $buttons,
            'catalog_key' => $catalog,
            'catalog_snapshot' => $snapshot,
        ];
    }

    private function buttonId(string $catalog, string $prefix, string $code): string
    {
        if ($catalog === 'tipos_ausentismo' && $code === 'atencion_familiar_enfermo') {
            return 'ausentismo_familiar_enfermo';
        }

        return $prefix.'_'.$code;
    }

    public function label(string $catalog, ?string $code): ?string
    {
        if ($code === null) {
            return null;
        }

        [$model] = $this->definition($catalog);

        return $model::query()->where('codigo', $code)->value('nombre');
    }

    private function definition(string $catalog): array
    {
        if (! isset(self::CATALOGS[$catalog])) {
            throw new \InvalidArgumentException("Unknown chat catalog [{$catalog}].");
        }

        return self::CATALOGS[$catalog];
    }
}
