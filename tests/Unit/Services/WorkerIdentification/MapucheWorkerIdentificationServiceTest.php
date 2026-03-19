<?php

namespace Tests\Unit\Services\WorkerIdentification;

use App\Services\Mapuche\Contracts\MapucheWorkerProvider;
use App\Services\Mapuche\MapucheWorkerRecord;
use App\Services\WorkerIdentification\MapucheWorkerIdentificationService;
use Tests\TestCase;

class MapucheWorkerIdentificationServiceTest extends TestCase
{
    public function test_maps_mapuche_record_to_generic_worker_identification_record(): void
    {
        $service = new MapucheWorkerIdentificationService(new class implements MapucheWorkerProvider
        {
            public function findByLegajo(string $legajo): ?MapucheWorkerRecord
            {
                return new MapucheWorkerRecord($legajo, 'Ana Perez', 'Sede Central', 'Manana', 'mock_config');
            }
        });

        $worker = $service->findByLegajo('12345');

        $this->assertNotNull($worker);
        $this->assertSame('12345', $worker->legajo);
        $this->assertSame('Ana Perez', $worker->nombreCompleto);
        $this->assertSame('mock_config', $worker->source);
        $this->assertSame('mapuche', $worker->provider);
    }

    public function test_returns_null_when_provider_cannot_resolve_legajo(): void
    {
        $service = new MapucheWorkerIdentificationService(new class implements MapucheWorkerProvider
        {
            public function findByLegajo(string $legajo): ?MapucheWorkerRecord
            {
                return null;
            }
        });

        $this->assertNull($service->findByLegajo('99999'));
    }
}
