<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use LogicException;

abstract class CatalogoMaestro extends Model
{
    protected $fillable = ['codigo', 'nombre', 'activo', 'orden'];

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
            'orden' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::updating(function (self $record): void {
            if ($record->isDirty('codigo')) {
                throw new LogicException('El código de un catálogo es estable y no se puede modificar.');
            }
        });

        static::deleting(function (): never {
            throw new LogicException('Los catálogos no se eliminan; deben desactivarse.');
        });
    }
}
