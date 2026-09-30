<?php

namespace App\Filament\Resources;

use App\Models\CatalogoMaestro;
use App\Models\TipoAusentismo;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

abstract class CatalogoResource extends Resource
{
    protected static string|\UnitEnum|null $navigationGroup = 'Catálogos';

    public static function form(Schema $schema): Schema
    {
        $fields = [
            TextInput::make('codigo')
                ->label('Código estable')
                ->required()
                ->alphaDash()
                ->maxLength(80)
                ->unique(ignoreRecord: true)
                ->disabled(fn (string $operation): bool => $operation === 'edit'),
            TextInput::make('nombre')
                ->label('Nombre visible en el chat')
                ->required()
                ->maxLength(20),
        ];

        if (static::$model === TipoAusentismo::class) {
            $fields[] = Toggle::make('requiere_datos_familiar')
                ->label('Solicita datos del familiar')
                ->default(false);
        }

        $fields[] = TextInput::make('orden')->label('Orden')->integer()->minValue(0)->required()->default(10);
        $fields[] = Toggle::make('activo')->label('Disponible en el chat')->default(true)->required();

        return $schema->components($fields);
    }

    public static function table(Table $table): Table
    {
        $columns = [
            TextColumn::make('codigo')->label('Código')->searchable()->sortable(),
            TextColumn::make('nombre')->label('Nombre')->searchable()->sortable(),
        ];
        if (static::$model === TipoAusentismo::class) {
            $columns[] = IconColumn::make('requiere_datos_familiar')->label('Datos de familiar')->boolean();
        }
        $columns[] = IconColumn::make('activo')->label('Activo')->boolean()->sortable();
        $columns[] = TextColumn::make('orden')->label('Orden')->numeric()->sortable();

        return $table
            ->defaultSort('orden')
            ->columns($columns)
            ->recordActions([
                EditAction::make()
                    ->visible(fn (CatalogoMaestro $record): bool => static::canEdit($record)),
            ])
            ->toolbarActions([]);
    }

    public static function canViewAny(): bool
    {
        return (bool) auth()->user()?->can('catalogos.view');
    }

    public static function canView(Model $record): bool
    {
        return static::canViewAny();
    }

    public static function canCreate(): bool
    {
        return (bool) auth()->user()?->can('catalogos.manage');
    }

    public static function canEdit(Model $record): bool
    {
        return static::canCreate();
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }
}
