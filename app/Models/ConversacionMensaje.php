<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConversacionMensaje extends Model
{
    use HasFactory;

    public const DIRECCION_ENTRANTE = 'in';
    public const DIRECCION_SALIENTE = 'out';

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

    public function isIncoming(): bool
    {
        return $this->direccion === self::DIRECCION_ENTRANTE;
    }

    public function isOutgoing(): bool
    {
        return $this->direccion === self::DIRECCION_SALIENTE;
    }

    public function conversacion(): BelongsTo
    {
        return $this->belongsTo(Conversacion::class, 'conversacion_id');
    }
}
