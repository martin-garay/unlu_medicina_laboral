<?php

namespace App\Filament\Resources\AvisoResource\Pages;

use App\Filament\Resources\AvisoResource;
use App\Models\AnticipoCertificado;
use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Database\Eloquent\Collection;

class ViewAviso extends ViewRecord
{
    protected static string $resource = AvisoResource::class;

    protected string $view = 'filament.resources.aviso-resource.pages.view-aviso';

    public function getTitle(): string
    {
        return 'Detalle de aviso #' . $this->getRecord()->getKey();
    }

    /**
     * @return Collection<int, AnticipoCertificado>
     */
    public function anticiposCertificado(): Collection
    {
        return $this->getRecord()
            ->anticiposCertificado()
            ->orderBy('anticipos_certificado.created_at')
            ->orderBy('anticipos_certificado.id')
            ->get();
    }

    /**
     * @return Collection<int, AnticipoCertificado>
     */
    public function anticiposCertificadoLegacy(): Collection
    {
        return $this->getRecord()
            ->anticiposCertificadoLegacy()
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('volver')
                ->label(__('backoffice.actions.back'))
                ->icon('heroicon-o-arrow-left')
                ->color('gray')
                ->url(AvisoResource::getUrl('index')),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        unset($data['certificado_base64'], $data['metadata']);

        return $data;
    }
}
