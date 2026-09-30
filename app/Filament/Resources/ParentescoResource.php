<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ParentescoResource\Pages;
use App\Models\Parentesco;

class ParentescoResource extends CatalogoResource
{
    protected static ?string $model = Parentesco::class;

    protected static ?string $slug = 'parentescos';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-user-group';

    protected static ?int $navigationSort = 40;

    protected static ?string $modelLabel = 'parentesco';

    protected static ?string $pluralModelLabel = 'parentescos';

    public static function getPages(): array
    {
        return ['index' => Pages\ListParentescos::route('/'), 'create' => Pages\CreateParentesco::route('/create'), 'edit' => Pages\EditParentesco::route('/{record}/edit')];
    }
}
