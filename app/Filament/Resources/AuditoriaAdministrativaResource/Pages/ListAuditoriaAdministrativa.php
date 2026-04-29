<?php

namespace App\Filament\Resources\AuditoriaAdministrativaResource\Pages;

use App\Filament\Resources\AuditoriaAdministrativaResource;
use Filament\Resources\Pages\ListRecords;

class ListAuditoriaAdministrativa extends ListRecords
{
    protected static string $resource = AuditoriaAdministrativaResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
