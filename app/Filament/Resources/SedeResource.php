<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SedeResource\Pages;
use App\Models\Sede;

class SedeResource extends CatalogoResource
{
    protected static ?string $model = Sede::class;

    protected static ?string $slug = 'sedes';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-map-pin';

    protected static ?int $navigationSort = 10;

    protected static ?string $modelLabel = 'sede';

    protected static ?string $pluralModelLabel = 'sedes';

    public static function getPages(): array
    {
        return ['index' => Pages\ListSedes::route('/'), 'create' => Pages\CreateSede::route('/create'), 'edit' => Pages\EditSede::route('/{record}/edit')];
    }
}
