<?php

namespace App\Filament\Resources\TipoCertificadoResource\Pages;

use App\Filament\Resources\TipoCertificadoResource;
use Filament\Resources\Pages\EditRecord;

class EditTipoCertificado extends EditRecord
{
    protected static string $resource = TipoCertificadoResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
