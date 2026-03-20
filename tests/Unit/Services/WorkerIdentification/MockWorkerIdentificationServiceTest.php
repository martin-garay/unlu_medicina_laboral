<?php

namespace Tests\Unit\Services\WorkerIdentification;

use App\Services\WorkerIdentification\MockWorkerIdentificationService;
use Tests\TestCase;

class MockWorkerIdentificationServiceTest extends TestCase
{
    public function test_returns_configured_worker_record_when_legajo_exists(): void
    {
        config()->set('medicina_laboral.worker_identification.mock.records', [
            '12345' => [
                'nombre_completo' => 'Laura Diaz',
                'sede' => 'Sede Central',
                'jornada_laboral' => 'Manana',
            ],
        ]);

        $worker = (new MockWorkerIdentificationService())->findByLegajo('12345');

        $this->assertNotNull($worker);
        $this->assertSame('12345', $worker->legajo);
        $this->assertSame('Laura Diaz', $worker->nombreCompleto);
        $this->assertSame('mock_config', $worker->source);
        $this->assertSame('mock', $worker->provider);
    }

    public function test_returns_fallback_record_when_unknown_legajo_is_allowed(): void
    {
        config()->set('medicina_laboral.worker_identification.mock.records', []);
        config()->set('medicina_laboral.worker_identification.mock.accept_unknown_legajo', true);

        $worker = (new MockWorkerIdentificationService())->findByLegajo('99999');

        $this->assertNotNull($worker);
        $this->assertSame('99999', $worker->legajo);
        $this->assertSame('mock_fallback', $worker->source);
        $this->assertSame('mock', $worker->provider);
    }

    public function test_returns_null_when_unknown_legajo_is_not_allowed(): void
    {
        config()->set('medicina_laboral.worker_identification.mock.records', []);
        config()->set('medicina_laboral.worker_identification.mock.accept_unknown_legajo', false);

        $worker = (new MockWorkerIdentificationService())->findByLegajo('99999');

        $this->assertNull($worker);
    }
}
