<?php

namespace App\Filament\Resources\AnticipoCertificadoResource\Pages;

use App\Filament\Resources\AnticipoCertificadoResource;
use Filament\Resources\Pages\ListRecords;

class ListAnticipoCertificados extends ListRecords
{
    protected static string $resource = AnticipoCertificadoResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
