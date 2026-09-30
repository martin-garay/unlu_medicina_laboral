<?php

namespace Tests\Concerns;

use Database\Seeders\ChatCatalogsSeeder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

trait CreatesChatCatalogSchema
{
    protected function createChatCatalogSchema(): void
    {
        foreach (['sedes', 'tipos_ausentismo', 'tipos_certificado', 'parentescos'] as $tableName) {
            Schema::dropIfExists($tableName);
            Schema::create($tableName, function (Blueprint $table) use ($tableName): void {
                $table->id();
                $table->string('codigo', 80)->unique();
                $table->string('nombre', 120);
                if ($tableName === 'tipos_ausentismo') {
                    $table->boolean('requiere_datos_familiar')->default(false);
                }
                $table->boolean('activo')->default(true)->index();
                $table->unsignedInteger('orden')->default(0);
                $table->timestamps();
            });
        }

        $this->seed(ChatCatalogsSeeder::class);
    }
}
