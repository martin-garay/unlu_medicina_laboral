<?php

namespace App\Filament\Resources\AnticipoCertificadoResource\Pages;

use App\Filament\Resources\AnticipoCertificadoResource;
use App\Models\AnticipoCertificadoArchivo;
use App\Models\Aviso;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Database\Eloquent\Collection;

class ViewAnticipoCertificado extends ViewRecord
{
    protected static string $resource = AnticipoCertificadoResource::class;

    protected string $view = 'filament.resources.anticipo-certificado-resource.pages.view-anticipo-certificado';

    public function getTitle(): string
    {
        return 'Detalle de certificado #' . $this->getRecord()->getKey();
    }

    /**
     * @return Collection<int, Aviso>
     */
    public function avisos(): Collection
    {
        return $this->getRecord()
            ->avisos()
            ->orderBy('avisos.created_at')
            ->orderBy('avisos.id')
            ->get();
    }

    /**
     * @return Collection<int, AnticipoCertificadoArchivo>
     */
    public function archivos(): Collection
    {
        return $this->getRecord()
            ->archivos()
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();
    }

    protected function getHeaderActions(): array
    {
        return [];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        unset($data['metadata']);

        return $data;
    }
}
