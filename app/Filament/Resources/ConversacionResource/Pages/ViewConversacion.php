<?php

namespace App\Filament\Resources\ConversacionResource\Pages;

use App\Filament\Resources\ConversacionResource;
use App\Models\ConversacionEvento;
use App\Models\ConversacionMensaje;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Database\Eloquent\Collection;

class ViewConversacion extends ViewRecord
{
    protected static string $resource = ConversacionResource::class;

    protected string $view = 'filament.resources.conversacion-resource.pages.view-conversacion';

    public function getTitle(): string
    {
        return 'Historial de conversacion #' . $this->getRecord()->getKey();
    }

    /**
     * @return Collection<int, ConversacionMensaje>
     */
    public function mensajes(): Collection
    {
        return $this->getRecord()
            ->mensajes()
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();
    }

    /**
     * @return Collection<int, ConversacionEvento>
     */
    public function eventos(): Collection
    {
        return $this->getRecord()
            ->eventos()
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
