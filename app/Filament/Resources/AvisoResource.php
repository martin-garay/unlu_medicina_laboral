<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AvisoResource\Pages;
use App\Models\Aviso;
use Filament\Actions\ViewAction;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class AvisoResource extends Resource
{
    protected static ?string $model = Aviso::class;

    protected static ?string $slug = 'avisos';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    protected static string|\UnitEnum|null $navigationGroup = 'Medicina laboral';

    protected static ?int $navigationSort = 20;

    protected static ?string $modelLabel = 'aviso';

    protected static ?string $pluralModelLabel = 'avisos';

    public static function form(Schema $schema): Schema
    {
        return $schema;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                TextColumn::make('dni')
                    ->label('DNI')
                    ->searchable()
                    ->sortable()
                    ->placeholder('-'),
                TextColumn::make('nombre_completo')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable()
                    ->placeholder('-'),
                TextColumn::make('legajo')
                    ->label('Legajo')
                    ->searchable()
                    ->sortable()
                    ->placeholder('-'),
                TextColumn::make('sede')
                    ->label('Sede')
                    ->sortable()
                    ->placeholder('-'),
                TextColumn::make('tipo')
                    ->label('Tipo')
                    ->badge()
                    ->sortable()
                    ->placeholder('-'),
                TextColumn::make('estado')
                    ->label('Estado')
                    ->badge()
                    ->sortable()
                    ->placeholder('-'),
                TextColumn::make('tipo_ausentismo')
                    ->label('Ausentismo')
                    ->badge()
                    ->sortable()
                    ->placeholder('-'),
                TextColumn::make('fecha_inicio')
                    ->label('Fecha inicio')
                    ->date('Y-m-d')
                    ->sortable()
                    ->placeholder('-'),
                TextColumn::make('fecha_fin')
                    ->label('Fecha fin')
                    ->date('Y-m-d')
                    ->sortable()
                    ->placeholder('-'),
                TextColumn::make('cantidad_dias')
                    ->label('Dias')
                    ->numeric()
                    ->sortable()
                    ->placeholder('-'),
                TextColumn::make('wa_number')
                    ->label('WhatsApp')
                    ->searchable()
                    ->sortable()
                    ->placeholder('-'),
                TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime('Y-m-d H:i')
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('estado')
                    ->label('Estado')
                    ->options(fn (): array => static::distinctOptions('estado')),
                SelectFilter::make('tipo')
                    ->label('Tipo')
                    ->options(fn (): array => static::distinctOptions('tipo')),
                SelectFilter::make('tipo_ausentismo')
                    ->label('Ausentismo')
                    ->options(fn (): array => static::distinctOptions('tipo_ausentismo')),
                SelectFilter::make('sede')
                    ->label('Sede')
                    ->options(fn (): array => static::distinctOptions('sede')),
            ])
            ->actions([
                ViewAction::make()
                    ->label('Ver detalle')
                    ->icon('heroicon-o-eye')
                    ->url(fn (Aviso $record): string => static::getUrl('view', ['record' => $record]))
                    ->visible(fn (Aviso $record): bool => static::canView($record)),
            ])
            ->bulkActions([]);
    }

    public static function canViewAny(): bool
    {
        return (bool) auth()->user()?->can('avisos.view');
    }

    public static function canView(Model $record): bool
    {
        return static::canViewAny();
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    /**
     * @return array<string, string>
     */
    private static function distinctOptions(string $column): array
    {
        return Aviso::query()
            ->whereNotNull($column)
            ->distinct()
            ->orderBy($column)
            ->pluck($column, $column)
            ->all();
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAvisos::route('/'),
            'view' => Pages\ViewAviso::route('/{record}'),
        ];
    }
}
