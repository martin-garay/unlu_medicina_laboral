<?php

namespace App\Domain\Auditoria\Services;

use App\Models\AuditoriaAdministrativa;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

class AuditoriaAdministrativaService
{
    public const ORIGIN_FILAMENT = 'filament';
    public const ORIGIN_COMMAND = 'command';
    public const ORIGIN_JOB = 'job';
    public const ORIGIN_SYSTEM = 'system';

    public function record(
        string $action,
        string $origin,
        ?User $actor = null,
        ?Model $auditable = null,
        array $beforeValues = [],
        array $afterValues = [],
        array $metadata = [],
    ): AuditoriaAdministrativa {
        $action = trim($action);
        $origin = trim($origin);

        if ($action === '') {
            throw new InvalidArgumentException('Administrative audit action cannot be empty.');
        }

        if (! in_array($origin, $this->allowedOrigins(), true)) {
            throw new InvalidArgumentException("Unsupported administrative audit origin [{$origin}].");
        }

        return AuditoriaAdministrativa::create([
            'actor_user_id' => $actor?->id,
            'action' => $action,
            'origin' => $origin,
            'auditable_type' => $auditable ? $auditable::class : null,
            'auditable_id' => $auditable?->getKey(),
            'before_values' => $beforeValues === [] ? null : $beforeValues,
            'after_values' => $afterValues === [] ? null : $afterValues,
            'metadata' => $metadata === [] ? null : $metadata,
        ]);
    }

    /**
     * @return array<int, string>
     */
    public function allowedOrigins(): array
    {
        return [
            self::ORIGIN_FILAMENT,
            self::ORIGIN_COMMAND,
            self::ORIGIN_JOB,
            self::ORIGIN_SYSTEM,
        ];
    }
}
