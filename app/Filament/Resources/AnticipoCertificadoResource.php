<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AnticipoCertificadoResource\Pages;
use App\Models\AnticipoCertificado;
use Filament\Forms\Components\DatePicker;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class AnticipoCertificadoResource extends Resource
{
    protected static ?string $model = AnticipoCertificado::class;

    protected static ?string $slug = 'certificados-medicos';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-check';

    protected static string|\UnitEnum|null $navigationGroup = 'Medicina laboral';

    protected static ?int $navigationSort = 30;

    protected static ?string $modelLabel = 'certificado medico';

    protected static ?string $pluralModelLabel = 'certificados medicos';

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
                TextColumn::make('numero_anticipo')
                    ->label('Numero')
                    ->searchable()
                    ->sortable()
                    ->placeholder('-'),
                TextColumn::make('uuid')
                    ->label('UUID')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('wa_number')
                    ->label('WhatsApp')
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
                TextColumn::make('tipo_certificado')
                    ->label('Tipo')
                    ->badge()
                    ->sortable()
                    ->placeholder('-'),
                TextColumn::make('estado')
                    ->label('Estado')
                    ->badge()
                    ->sortable()
                    ->placeholder('-'),
                TextColumn::make('registrado_en')
                    ->label('Registrado')
                    ->dateTime('Y-m-d H:i')
                    ->sortable()
                    ->placeholder('-'),
                TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime('Y-m-d H:i')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('archivos_count')
                    ->label('Archivos')
                    ->numeric()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('estado')
                    ->label('Estado')
                    ->options(fn (): array => static::distinctOptions('estado')),
                SelectFilter::make('tipo_certificado')
                    ->label('Tipo certificado')
                    ->options(fn (): array => static::distinctOptions('tipo_certificado')),
                SelectFilter::make('sede')
                    ->label('Sede')
                    ->options(fn (): array => static::distinctOptions('sede')),
                Filter::make('registrado_en')
                    ->label('Registrado')
                    ->schema([
                        DatePicker::make('desde')->label('Desde'),
                        DatePicker::make('hasta')->label('Hasta'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => static::applyDateRangeFilter(
                        $query,
                        'registrado_en',
                        $data,
                    )),
                Filter::make('created_at')
                    ->label('Creado')
                    ->schema([
                        DatePicker::make('desde')->label('Desde'),
                        DatePicker::make('hasta')->label('Hasta'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => static::applyDateRangeFilter(
                        $query,
                        'created_at',
                        $data,
                    )),
            ])
            ->actions([])
            ->bulkActions([]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->withCount('archivos');
    }

    public static function canViewAny(): bool
    {
        return (bool) auth()->user()?->can('certificados.view');
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
        return AnticipoCertificado::query()
            ->whereNotNull($column)
            ->distinct()
            ->orderBy($column)
            ->pluck($column, $column)
            ->all();
    }

    private static function applyDateRangeFilter(Builder $query, string $column, array $data): Builder
    {
        return $query
            ->when($data['desde'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate($column, '>=', $date))
            ->when($data['hasta'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate($column, '<=', $date));
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAnticipoCertificados::route('/'),
        ];
    }
}
