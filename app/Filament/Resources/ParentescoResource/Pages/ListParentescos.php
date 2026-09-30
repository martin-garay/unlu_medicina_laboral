<?php

namespace App\Filament\Resources\ParentescoResource\Pages;

use App\Filament\Resources\ParentescoResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListParentescos extends ListRecords
{
    protected static string $resource = ParentescoResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
