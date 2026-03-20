<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AnticipoCertificadoArchivo extends Model
{
    use HasFactory;

    protected $table = 'anticipo_certificado_archivos';

    protected $fillable = [
        'uuid',
        'anticipo_certificado_id',
        'conversacion_id',
        'provider_file_id',
        'nombre_original',
        'mime_type',
        'extension',
        'size_bytes',
        'storage_disk',
        'storage_path',
        'hash_archivo',
        'estado_validacion',
        'motivo_rechazo',
        'metadata',
    ];

    protected $casts = [
        'size_bytes' => 'integer',
        'metadata' => 'array',
    ];

    public function anticipoCertificado(): BelongsTo
    {
        return $this->belongsTo(AnticipoCertificado::class, 'anticipo_certificado_id');
    }

    public function conversacion(): BelongsTo
    {
        return $this->belongsTo(Conversacion::class, 'conversacion_id');
    }
}
