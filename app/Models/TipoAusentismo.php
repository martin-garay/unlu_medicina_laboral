<?php

namespace App\Models;

class TipoAusentismo extends CatalogoMaestro
{
    protected $table = 'tipos_ausentismo';

    protected $fillable = ['codigo', 'nombre', 'requiere_datos_familiar', 'activo', 'orden'];

    protected function casts(): array
    {
        return parent::casts() + ['requiere_datos_familiar' => 'boolean'];
    }
}
