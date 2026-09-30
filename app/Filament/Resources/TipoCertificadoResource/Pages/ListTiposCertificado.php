<?php

namespace App\Filament\Resources\TipoCertificadoResource\Pages;

use App\Filament\Resources\TipoCertificadoResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListTiposCertificado extends ListRecords
{
    protected static string $resource = TipoCertificadoResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
