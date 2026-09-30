<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TipoCertificadoResource\Pages;
use App\Models\TipoCertificado;

class TipoCertificadoResource extends CatalogoResource
{
    protected static ?string $model = TipoCertificado::class;

    protected static ?string $slug = 'tipos-certificado';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-check';

    protected static ?int $navigationSort = 30;

    protected static ?string $modelLabel = 'tipo de certificado';

    protected static ?string $pluralModelLabel = 'tipos de certificado';

    public static function getPages(): array
    {
        return ['index' => Pages\ListTiposCertificado::route('/'), 'create' => Pages\CreateTipoCertificado::route('/create'), 'edit' => Pages\EditTipoCertificado::route('/{record}/edit')];
    }
}
