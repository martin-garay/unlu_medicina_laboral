<?php

namespace App\Filament\Resources\AuditoriaAdministrativaResource\Pages;

use App\Filament\Resources\AuditoriaAdministrativaResource;
use Filament\Resources\Pages\ViewRecord;

class ViewAuditoriaAdministrativa extends ViewRecord
{
    protected static string $resource = AuditoriaAdministrativaResource::class;

    protected string $view = 'filament.resources.auditoria-administrativa-resource.pages.view-auditoria-administrativa';

    public function getTitle(): string
    {
        return 'Detalle de auditoria #' . $this->getRecord()->getKey();
    }

    protected function getHeaderActions(): array
    {
        return [];
    }
}
