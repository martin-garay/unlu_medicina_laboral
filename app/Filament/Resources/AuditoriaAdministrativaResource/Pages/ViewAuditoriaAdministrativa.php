<?php

namespace App\Filament\Resources\AuditoriaAdministrativaResource\Pages;

use App\Filament\Resources\AuditoriaAdministrativaResource;
use Filament\Actions\Action;
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
        return [
            Action::make('volver')
                ->label(__('backoffice.actions.back'))
                ->icon('heroicon-o-arrow-left')
                ->color('gray')
                ->url(AuditoriaAdministrativaResource::getUrl('index')),
        ];
    }
}
