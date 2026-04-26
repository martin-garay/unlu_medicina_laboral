<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Aviso extends Model
{
    use HasFactory;

    protected $fillable = [
        'conversacion_id',
        'dni',
        'nombre_completo',
        'legajo',
        'sede',
        'jornada_laboral',
        'tipo',
        'estado',
        'tipo_ausentismo',
        'fecha_inicio',
        'fecha_fin',
        'cantidad_dias',
        'certificado_base64',
        'motivo',
        'domicilio_circunstancial',
        'observaciones',
        'wa_number',
        'metadata',
    ];

    protected $casts = [
        'fecha_inicio' => 'date',
        'fecha_fin' => 'date',
        'cantidad_dias' => 'integer',
        'metadata' => 'array',
    ];

    public function conversacion(): BelongsTo
    {
        return $this->belongsTo(Conversacion::class, 'conversacion_id');
    }

    public function anticiposCertificado(): HasMany
    {
        return $this->hasMany(AnticipoCertificado::class, 'aviso_id');
    }
}
