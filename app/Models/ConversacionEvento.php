<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConversacionEvento extends Model
{
    use HasFactory;

    protected $table = 'conversacion_eventos';

    protected $fillable = [
        'uuid',
        'conversacion_id',
        'tipo_evento',
        'step_key',
        'descripcion',
        'codigo',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function conversacion(): BelongsTo
    {
        return $this->belongsTo(Conversacion::class, 'conversacion_id');
    }
}
