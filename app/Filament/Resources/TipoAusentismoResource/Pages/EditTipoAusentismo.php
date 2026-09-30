<?php

namespace App\Filament\Resources\TipoAusentismoResource\Pages;

use App\Filament\Resources\TipoAusentismoResource;
use Filament\Resources\Pages\EditRecord;

class EditTipoAusentismo extends EditRecord
{
    protected static string $resource = TipoAusentismoResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
