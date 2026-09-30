<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['sedes', 'tipos_ausentismo', 'tipos_certificado', 'parentescos'] as $tableName) {
            Schema::create($tableName, function (Blueprint $table) {
                $table->id();
                $table->string('codigo', 80)->unique();
                $table->string('nombre', 120);
                $table->boolean('activo')->default(true)->index();
                $table->unsignedInteger('orden')->default(0);
                $table->timestamps();
            });
        }

        Schema::table('tipos_ausentismo', function (Blueprint $table) {
            $table->boolean('requiere_datos_familiar')->default(false)->after('nombre');
        });

        $now = now();
        DB::table('sedes')->insert([
            ['codigo' => 'central', 'nombre' => 'Sede Central', 'activo' => true, 'orden' => 10, 'created_at' => $now, 'updated_at' => $now],
            ['codigo' => 'campus', 'nombre' => 'Campus Luján', 'activo' => true, 'orden' => 20, 'created_at' => $now, 'updated_at' => $now],
            ['codigo' => 'delegacion', 'nombre' => 'San Fernando', 'activo' => true, 'orden' => 30, 'created_at' => $now, 'updated_at' => $now],
        ]);
        DB::table('tipos_ausentismo')->insert([
            ['codigo' => 'por_enfermedad', 'nombre' => 'Por enfermedad', 'requiere_datos_familiar' => false, 'activo' => true, 'orden' => 10, 'created_at' => $now, 'updated_at' => $now],
            ['codigo' => 'atencion_familiar_enfermo', 'nombre' => 'Familiar enfermo', 'requiere_datos_familiar' => true, 'activo' => true, 'orden' => 20, 'created_at' => $now, 'updated_at' => $now],
        ]);
        DB::table('tipos_certificado')->insert([
            ['codigo' => 'manuscrito', 'nombre' => 'Manuscrito', 'activo' => true, 'orden' => 10, 'created_at' => $now, 'updated_at' => $now],
            ['codigo' => 'electronico', 'nombre' => 'Electrónico', 'activo' => true, 'orden' => 20, 'created_at' => $now, 'updated_at' => $now],
        ]);
        DB::table('parentescos')->insert([
            ['codigo' => 'madre', 'nombre' => 'Madre', 'activo' => true, 'orden' => 10, 'created_at' => $now, 'updated_at' => $now],
            ['codigo' => 'padre', 'nombre' => 'Padre', 'activo' => true, 'orden' => 20, 'created_at' => $now, 'updated_at' => $now],
            ['codigo' => 'hijo_hija', 'nombre' => 'Hijo/a', 'activo' => true, 'orden' => 30, 'created_at' => $now, 'updated_at' => $now],
            ['codigo' => 'conyuge', 'nombre' => 'Cónyuge', 'activo' => true, 'orden' => 40, 'created_at' => $now, 'updated_at' => $now],
            ['codigo' => 'otro', 'nombre' => 'Otro', 'activo' => true, 'orden' => 50, 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('parentescos');
        Schema::dropIfExists('tipos_certificado');
        Schema::dropIfExists('tipos_ausentismo');
        Schema::dropIfExists('sedes');
    }
};
