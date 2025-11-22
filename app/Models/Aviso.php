<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Aviso extends Model
{
    use HasFactory;

    protected $fillable = [
        'dni',
        'tipo',
        'fecha_inicio',
        'cantidad_dias',
        'certificado_base64',
        'wa_number',
    ];
}
