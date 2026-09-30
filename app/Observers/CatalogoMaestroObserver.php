<?php

namespace App\Observers;

use App\Domain\Auditoria\Services\AuditoriaAdministrativaService;
use App\Models\CatalogoMaestro;
use Illuminate\Support\Facades\Auth;

class CatalogoMaestroObserver
{
    private array $before = [];

    public function created(CatalogoMaestro $record): void
    {
        if (! Auth::check()) {
            return;
        }

        app(AuditoriaAdministrativaService::class)->record(
            action: 'catalogo.created',
            origin: AuditoriaAdministrativaService::ORIGIN_FILAMENT,
            actor: Auth::user(),
            auditable: $record,
            afterValues: $this->values($record),
        );
    }

    public function updating(CatalogoMaestro $record): void
    {
        $this->before[spl_object_id($record)] = $this->values($record);
    }

    public function updated(CatalogoMaestro $record): void
    {
        if (! Auth::check()) {
            return;
        }

        $key = spl_object_id($record);
        $before = $this->before[$key] ?? [];
        unset($this->before[$key]);

        app(AuditoriaAdministrativaService::class)->record(
            action: 'catalogo.updated',
            origin: AuditoriaAdministrativaService::ORIGIN_FILAMENT,
            actor: Auth::user(),
            auditable: $record,
            beforeValues: $before,
            afterValues: $this->values($record),
        );
    }

    private function values(CatalogoMaestro $record): array
    {
        return array_intersect_key($record->getAttributes(), array_flip([
            'codigo', 'nombre', 'requiere_datos_familiar', 'activo', 'orden',
        ]));
    }
}
