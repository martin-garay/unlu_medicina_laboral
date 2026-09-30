<?php

namespace App\Filament\Resources\ParentescoResource\Pages;

use App\Filament\Resources\ParentescoResource;
use Filament\Resources\Pages\EditRecord;

class EditParentesco extends EditRecord
{
    protected static string $resource = ParentescoResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
