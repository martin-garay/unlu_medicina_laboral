<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AnticipoCertificado extends Model
{
    use HasFactory;

    protected $table = 'anticipos_certificado';

    protected $fillable = [
        'uuid',
        'numero_anticipo',
        'conversacion_id',
        'aviso_id',
        'wa_number',
        'nombre_completo',
        'legajo',
        'sede',
        'jornada_laboral',
        'tipo_certificado',
        'estado',
        'observaciones',
        'registrado_en',
        'metadata',
    ];

    protected $casts = [
        'registrado_en' => 'datetime',
        'metadata' => 'array',
    ];

    public function conversacion(): BelongsTo
    {
        return $this->belongsTo(Conversacion::class, 'conversacion_id');
    }

    public function aviso(): BelongsTo
    {
        return $this->belongsTo(Aviso::class, 'aviso_id');
    }

    public function archivos(): HasMany
    {
        return $this->hasMany(AnticipoCertificadoArchivo::class, 'anticipo_certificado_id');
    }
}
