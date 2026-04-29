<?php

namespace App\Filament\Resources\ConversacionResource\Pages;

use App\Filament\Resources\ConversacionResource;
use Filament\Resources\Pages\ListRecords;

class ListConversaciones extends ListRecords
{
    protected static string $resource = ConversacionResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
