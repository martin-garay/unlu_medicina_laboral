<?php

namespace App\Services\WorkerIdentification;

use App\Services\Mapuche\Contracts\MapucheWorkerProvider;
use App\Services\WorkerIdentification\Contracts\WorkerIdentificationService;

class MapucheWorkerIdentificationService implements WorkerIdentificationService
{
    public function __construct(
        private readonly MapucheWorkerProvider $mapucheWorkerProvider,
    ) {
    }

    public function findByLegajo(string $legajo): ?WorkerIdentificationRecord
    {
        $record = $this->mapucheWorkerProvider->findByLegajo($legajo);

        if ($record === null) {
            return null;
        }

        return new WorkerIdentificationRecord(
            $record->legajo,
            $record->nombreCompleto,
            $record->sede,
            $record->jornadaLaboral,
            $record->source,
            'mapuche',
        );
    }
}
