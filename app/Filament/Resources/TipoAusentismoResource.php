<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TipoAusentismoResource\Pages;
use App\Models\TipoAusentismo;

class TipoAusentismoResource extends CatalogoResource
{
    protected static ?string $model = TipoAusentismo::class;

    protected static ?string $slug = 'tipos-ausentismo';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?int $navigationSort = 20;

    protected static ?string $modelLabel = 'tipo de ausentismo';

    protected static ?string $pluralModelLabel = 'tipos de ausentismo';

    public static function getPages(): array
    {
        return ['index' => Pages\ListTiposAusentismo::route('/'), 'create' => Pages\CreateTipoAusentismo::route('/create'), 'edit' => Pages\EditTipoAusentismo::route('/{record}/edit')];
    }
}
