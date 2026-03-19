<?php

namespace App\Services\Mapuche;

use App\Services\Mapuche\Contracts\MapucheWorkerProvider;

class MockMapucheWorkerProvider implements MapucheWorkerProvider
{
    public function findByLegajo(string $legajo): ?MapucheWorkerRecord
    {
        $records = config('medicina_laboral.mapuche.mock.records', []);

        if (isset($records[$legajo]) && is_array($records[$legajo])) {
            return MapucheWorkerRecord::fromArray($legajo, $records[$legajo], 'mock_config');
        }

        if ((bool) config('medicina_laboral.mapuche.mock.accept_unknown_legajo', true)) {
            return new MapucheWorkerRecord($legajo, null, null, null, 'mock_fallback');
        }

        return null;
    }
}
