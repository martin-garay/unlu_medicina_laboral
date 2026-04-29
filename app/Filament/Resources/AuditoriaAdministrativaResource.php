<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AuditoriaAdministrativaResource\Pages;
use App\Models\AuditoriaAdministrativa;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class AuditoriaAdministrativaResource extends Resource
{
    protected static ?string $model = AuditoriaAdministrativa::class;

    protected static ?string $slug = 'auditoria-administrativa';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-shield-check';

    protected static string|\UnitEnum|null $navigationGroup = 'Seguridad';

    protected static ?int $navigationSort = 40;

    protected static ?string $modelLabel = 'evento de auditoria';

    protected static ?string $pluralModelLabel = 'auditoria administrativa';

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
                TextColumn::make('actor.name')
                    ->label('Actor')
                    ->searchable()
                    ->sortable()
                    ->placeholder('Sistema'),
                TextColumn::make('action')
                    ->label('Accion')
                    ->searchable()
                    ->sortable()
                    ->placeholder('-'),
                TextColumn::make('origin')
                    ->label('Origen')
                    ->badge()
                    ->searchable()
                    ->sortable()
                    ->placeholder('-'),
                TextColumn::make('auditable_type')
                    ->label('Auditable')
                    ->searchable()
                    ->sortable()
                    ->placeholder('-'),
                TextColumn::make('auditable_id')
                    ->label('Auditable ID')
                    ->searchable()
                    ->sortable()
                    ->placeholder('-'),
                TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('actor_user_id')
                    ->label('Actor')
                    ->relationship('actor', 'name')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('action')
                    ->label('Accion')
                    ->options(fn (): array => static::distinctOptions('action')),
                SelectFilter::make('origin')
                    ->label('Origen')
                    ->options(fn (): array => static::distinctOptions('origin')),
                SelectFilter::make('auditable_type')
                    ->label('Auditable')
                    ->options(fn (): array => static::distinctOptions('auditable_type')),
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
            ->actions([
                ViewAction::make()
                    ->label('Ver detalle')
                    ->icon('heroicon-o-eye')
                    ->url(fn (AuditoriaAdministrativa $record): string => static::getUrl('view', ['record' => $record]))
                    ->visible(fn (AuditoriaAdministrativa $record): bool => static::canView($record)),
            ])
            ->bulkActions([]);
    }

    public static function canViewAny(): bool
    {
        return (bool) auth()->user()?->can('auditoria.view');
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
        return AuditoriaAdministrativa::query()
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
            'index' => Pages\ListAuditoriaAdministrativa::route('/'),
            'view' => Pages\ViewAuditoriaAdministrativa::route('/{record}'),
        ];
    }
}
