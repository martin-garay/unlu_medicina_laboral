<?php

namespace App\Services\WorkerIdentification\Contracts;

use App\Services\WorkerIdentification\WorkerIdentificationRecord;

interface WorkerIdentificationService
{
    public function findByLegajo(string $legajo): ?WorkerIdentificationRecord;
}
