<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ConversacionResource\Pages;
use App\Models\Conversacion;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ConversacionResource extends Resource
{
    protected static ?string $model = Conversacion::class;

    protected static ?string $slug = 'conversaciones';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    protected static string|\UnitEnum|null $navigationGroup = 'Trazabilidad';

    protected static ?int $navigationSort = 10;

    protected static ?string $modelLabel = 'conversacion';

    protected static ?string $pluralModelLabel = 'conversaciones';

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
                TextColumn::make('wa_number')
                    ->label('WhatsApp')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('dni')
                    ->label('DNI')
                    ->searchable()
                    ->sortable()
                    ->placeholder('-'),
                TextColumn::make('uuid')
                    ->label('UUID')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('canal')
                    ->label('Canal')
                    ->badge()
                    ->sortable()
                    ->placeholder('-'),
                TextColumn::make('tipo_flujo')
                    ->label('Flujo')
                    ->badge()
                    ->sortable()
                    ->placeholder('-'),
                TextColumn::make('estado_actual')
                    ->label('Estado actual')
                    ->badge()
                    ->sortable()
                    ->placeholder('-'),
                TextColumn::make('paso_actual')
                    ->label('Paso actual')
                    ->sortable()
                    ->placeholder('-'),
                IconColumn::make('activa')
                    ->label('Activa')
                    ->boolean()
                    ->sortable(),
                TextColumn::make('cantidad_mensajes_recibidos')
                    ->label('Recibidos')
                    ->numeric()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('cantidad_mensajes_enviados')
                    ->label('Enviados')
                    ->numeric()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('cantidad_mensajes_validos')
                    ->label('Validos')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('cantidad_mensajes_invalidos')
                    ->label('Invalidos')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('cantidad_intentos_totales')
                    ->label('Intentos')
                    ->numeric()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('ultimo_mensaje_recibido_en')
                    ->label('Ultimo recibido')
                    ->dateTime('Y-m-d H:i')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('ultimo_mensaje_enviado_en')
                    ->label('Ultimo enviado')
                    ->dateTime('Y-m-d H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('finalizada_en')
                    ->label('Finalizada')
                    ->dateTime('Y-m-d H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->label('Creada')
                    ->dateTime('Y-m-d H:i')
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('canal')
                    ->label('Canal')
                    ->options([
                        Conversacion::CANAL_WHATSAPP => 'WhatsApp',
                        Conversacion::CANAL_INTERNO => 'Chat interno',
                    ]),
                SelectFilter::make('tipo_flujo')
                    ->label('Flujo')
                    ->options(fn (): array => static::distinctOptions('tipo_flujo')),
                SelectFilter::make('estado_actual')
                    ->label('Estado actual')
                    ->options(fn (): array => static::distinctOptions('estado_actual')),
                TernaryFilter::make('activa')
                    ->label('Activa')
                    ->trueLabel('Activas')
                    ->falseLabel('Inactivas'),
                Filter::make('ultimo_mensaje_recibido_en')
                    ->label('Ultimo mensaje recibido')
                    ->schema([
                        DatePicker::make('desde')->label('Desde'),
                        DatePicker::make('hasta')->label('Hasta'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => static::applyDateRangeFilter(
                        $query,
                        'ultimo_mensaje_recibido_en',
                        $data,
                    )),
                Filter::make('finalizada_en')
                    ->label('Finalizada')
                    ->schema([
                        DatePicker::make('desde')->label('Desde'),
                        DatePicker::make('hasta')->label('Hasta'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => static::applyDateRangeFilter(
                        $query,
                        'finalizada_en',
                        $data,
                    )),
                Filter::make('created_at')
                    ->label('Creada')
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
                    ->label('Ver historial')
                    ->icon('heroicon-o-eye')
                    ->url(fn (Conversacion $record): string => static::getUrl('view', ['record' => $record]))
                    ->visible(fn (Conversacion $record): bool => static::canView($record)),
            ])
            ->bulkActions([]);
    }

    public static function canViewAny(): bool
    {
        return (bool) auth()->user()?->can('conversaciones.view');
    }

    /**
     * @return array<string, string>
     */
    private static function distinctOptions(string $column): array
    {
        return Conversacion::query()
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

    public static function canView(Model $record): bool
    {
        return static::canViewHistory();
    }

    public static function canViewHistory(): bool
    {
        return (bool) auth()->user()?->can('conversaciones.historial.view');
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

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListConversaciones::route('/'),
            'view' => Pages\ViewConversacion::route('/{record}'),
        ];
    }
}
