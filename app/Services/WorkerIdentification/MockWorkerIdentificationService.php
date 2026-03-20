<?php

namespace App\Services\WorkerIdentification;

use App\Services\WorkerIdentification\Contracts\WorkerIdentificationService;

class MockWorkerIdentificationService implements WorkerIdentificationService
{
    public function findByLegajo(string $legajo): ?WorkerIdentificationRecord
    {
        $records = config('medicina_laboral.worker_identification.mock.records', []);

        if (isset($records[$legajo]) && is_array($records[$legajo])) {
            return new WorkerIdentificationRecord(
                $legajo,
                $records[$legajo]['nombre_completo'] ?? null,
                $records[$legajo]['sede'] ?? null,
                $records[$legajo]['jornada_laboral'] ?? null,
                'mock_config',
                'mock'
            );
        }

        if ((bool) config('medicina_laboral.worker_identification.mock.accept_unknown_legajo', true)) {
            return new WorkerIdentificationRecord(
                $legajo,
                null,
                null,
                null,
                'mock_fallback',
                'mock'
            );
        }

        return null;
    }
}
