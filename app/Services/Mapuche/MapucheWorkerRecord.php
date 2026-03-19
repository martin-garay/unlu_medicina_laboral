<?php

namespace App\Services\Mapuche;

class MapucheWorkerRecord
{
    public function __construct(
        public readonly string $legajo,
        public readonly ?string $nombreCompleto,
        public readonly ?string $sede,
        public readonly ?string $jornadaLaboral,
        public readonly string $source,
    ) {
    }

    public static function fromArray(string $legajo, array $data, string $source = 'mock'): self
    {
        return new self(
            $legajo,
            $data['nombre_completo'] ?? null,
            $data['sede'] ?? null,
            $data['jornada_laboral'] ?? null,
            $source,
        );
    }

    public function toArray(): array
    {
        return [
            'legajo' => $this->legajo,
            'nombre_completo' => $this->nombreCompleto,
            'sede' => $this->sede,
            'jornada_laboral' => $this->jornadaLaboral,
            'source' => $this->source,
        ];
    }
}
