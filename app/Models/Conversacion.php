<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Conversacion extends Model
{
    use HasFactory;

    protected $table = 'conversaciones';

    protected $fillable = [
        'uuid',
        'wa_number',
        'canal',
        'tipo_flujo',
        'estado_actual',
        'paso_actual',
        'activa',
        'cantidad_mensajes_recibidos',
        'cantidad_mensajes_enviados',
        'cantidad_mensajes_validos',
        'cantidad_mensajes_invalidos',
        'cantidad_intentos_actual',
        'cantidad_intentos_totales',
        'ultimo_mensaje_recibido_en',
        'ultimo_mensaje_enviado_en',
        'primer_umbral_notificado_en',
        'segundo_umbral_notificado_en',
        'expira_en',
        'finalizada_en',
        'motivo_finalizacion',
        'aviso_id',
        'metadata',
        'estado',
        'tipo',
        'dni',
    ];

    protected $casts = [
        'activa' => 'boolean',
        'cantidad_mensajes_recibidos' => 'integer',
        'cantidad_mensajes_enviados' => 'integer',
        'cantidad_mensajes_validos' => 'integer',
        'cantidad_mensajes_invalidos' => 'integer',
        'cantidad_intentos_actual' => 'integer',
        'cantidad_intentos_totales' => 'integer',
        'ultimo_mensaje_recibido_en' => 'datetime',
        'ultimo_mensaje_enviado_en' => 'datetime',
        'primer_umbral_notificado_en' => 'datetime',
        'segundo_umbral_notificado_en' => 'datetime',
        'expira_en' => 'datetime',
        'finalizada_en' => 'datetime',
        'metadata' => 'array',
    ];

    public function mensajes(): HasMany
    {
        return $this->hasMany(ConversacionMensaje::class, 'conversacion_id');
    }

    public function eventos(): HasMany
    {
        return $this->hasMany(ConversacionEvento::class, 'conversacion_id');
    }

    public function aviso(): BelongsTo
    {
        return $this->belongsTo(Aviso::class, 'aviso_id');
    }
}
