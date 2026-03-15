<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConversacionMensaje extends Model
{
    use HasFactory;

    protected $table = 'conversacion_mensajes';

    protected $fillable = [
        'uuid',
        'conversacion_id',
        'direccion',
        'provider_message_id',
        'tipo_mensaje',
        'step_key',
        'contenido_texto',
        'es_valido',
        'motivo_invalidez',
        'message_key',
        'template_name',
        'payload_crudo',
        'metadata',
    ];

    protected $casts = [
        'es_valido' => 'boolean',
        'payload_crudo' => 'array',
        'metadata' => 'array',
    ];

    public function conversacion(): BelongsTo
    {
        return $this->belongsTo(Conversacion::class, 'conversacion_id');
    }
}
