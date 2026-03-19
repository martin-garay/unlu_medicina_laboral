<?php

namespace App\Services\Mapuche\Contracts;

use App\Services\Mapuche\MapucheWorkerRecord;

interface MapucheWorkerProvider
{
    public function findByLegajo(string $legajo): ?MapucheWorkerRecord;
}
