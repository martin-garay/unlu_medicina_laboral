<?php

namespace Tests\Unit\Services\Mapuche;

use App\Services\Mapuche\MockMapucheWorkerProvider;
use Tests\TestCase;

class MockMapucheWorkerProviderTest extends TestCase
{
    public function test_returns_configured_worker_record_when_legajo_exists(): void
    {
        config()->set('medicina_laboral.mapuche.mock.records', [
            '12345' => [
                'nombre_completo' => 'Laura Diaz',
                'sede' => 'Sede Central',
                'jornada_laboral' => 'Manana',
            ],
        ]);

        $worker = (new MockMapucheWorkerProvider())->findByLegajo('12345');

        $this->assertNotNull($worker);
        $this->assertSame('12345', $worker->legajo);
        $this->assertSame('Laura Diaz', $worker->nombreCompleto);
        $this->assertSame('mock_config', $worker->source);
    }

    public function test_returns_fallback_record_when_unknown_legajo_is_allowed(): void
    {
        config()->set('medicina_laboral.mapuche.mock.records', []);
        config()->set('medicina_laboral.mapuche.mock.accept_unknown_legajo', true);

        $worker = (new MockMapucheWorkerProvider())->findByLegajo('99999');

        $this->assertNotNull($worker);
        $this->assertSame('99999', $worker->legajo);
        $this->assertSame('mock_fallback', $worker->source);
    }

    public function test_returns_null_when_unknown_legajo_is_not_allowed(): void
    {
        config()->set('medicina_laboral.mapuche.mock.records', []);
        config()->set('medicina_laboral.mapuche.mock.accept_unknown_legajo', false);

        $worker = (new MockMapucheWorkerProvider())->findByLegajo('99999');

        $this->assertNull($worker);
    }
}
