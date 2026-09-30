<?php

namespace Database\Seeders;

use App\Models\Parentesco;
use App\Models\Sede;
use App\Models\TipoAusentismo;
use App\Models\TipoCertificado;
use Illuminate\Database\Seeder;

class ChatCatalogsSeeder extends Seeder
{
    public function run(): void
    {
        $this->seed(Sede::class, [
            ['codigo' => 'central', 'nombre' => 'Sede Central', 'orden' => 10],
            ['codigo' => 'campus', 'nombre' => 'Campus Luján', 'orden' => 20],
            ['codigo' => 'delegacion', 'nombre' => 'San Fernando', 'orden' => 30],
        ]);

        $this->seed(TipoAusentismo::class, [
            ['codigo' => 'por_enfermedad', 'nombre' => 'Por enfermedad', 'requiere_datos_familiar' => false, 'orden' => 10],
            ['codigo' => 'atencion_familiar_enfermo', 'nombre' => 'Familiar enfermo', 'requiere_datos_familiar' => true, 'orden' => 20],
        ]);

        $this->seed(TipoCertificado::class, [
            ['codigo' => 'manuscrito', 'nombre' => 'Manuscrito', 'orden' => 10],
            ['codigo' => 'electronico', 'nombre' => 'Electrónico', 'orden' => 20],
        ]);

        $this->seed(Parentesco::class, [
            ['codigo' => 'madre', 'nombre' => 'Madre', 'orden' => 10],
            ['codigo' => 'padre', 'nombre' => 'Padre', 'orden' => 20],
            ['codigo' => 'hijo_hija', 'nombre' => 'Hijo/a', 'orden' => 30],
            ['codigo' => 'conyuge', 'nombre' => 'Cónyuge', 'orden' => 40],
            ['codigo' => 'otro', 'nombre' => 'Otro', 'orden' => 50],
        ]);
    }

    private function seed(string $model, array $items): void
    {
        foreach ($items as $item) {
            $model::query()->firstOrCreate(
                ['codigo' => $item['codigo']],
                $item + ['activo' => true],
            );
        }
    }
}
