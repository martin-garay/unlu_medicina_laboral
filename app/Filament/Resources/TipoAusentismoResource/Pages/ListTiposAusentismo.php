<?php

namespace App\Filament\Resources\TipoAusentismoResource\Pages;

use App\Filament\Resources\TipoAusentismoResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListTiposAusentismo extends ListRecords
{
    protected static string $resource = TipoAusentismoResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
