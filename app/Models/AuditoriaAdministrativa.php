<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AuditoriaAdministrativa extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'auditoria_administrativa';

    protected $fillable = [
        'actor_user_id',
        'action',
        'origin',
        'auditable_type',
        'auditable_id',
        'before_values',
        'after_values',
        'metadata',
    ];

    protected $casts = [
        'before_values' => 'array',
        'after_values' => 'array',
        'metadata' => 'array',
    ];

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }

    public function auditable(): MorphTo
    {
        return $this->morphTo();
    }
}
