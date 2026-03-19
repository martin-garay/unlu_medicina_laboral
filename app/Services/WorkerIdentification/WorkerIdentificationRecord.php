<?php

namespace App\Services\WorkerIdentification;

class WorkerIdentificationRecord
{
    public function __construct(
        public readonly string $legajo,
        public readonly ?string $nombreCompleto,
        public readonly ?string $sede,
        public readonly ?string $jornadaLaboral,
        public readonly string $source,
        public readonly string $provider,
    ) {
    }

    public function toArray(): array
    {
        return [
            'legajo' => $this->legajo,
            'nombre_completo' => $this->nombreCompleto,
            'sede' => $this->sede,
            'jornada_laboral' => $this->jornadaLaboral,
            'source' => $this->source,
            'provider' => $this->provider,
        ];
    }
}
