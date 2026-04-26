<?php

namespace Tests\Feature\Database;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AnticipoCertificadoAvisoMigrationTest extends TestCase
{
    public function test_migration_backfills_pivot_from_legacy_aviso_id(): void
    {
        Schema::create('avisos', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
        });
        Schema::create('anticipos_certificado', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('aviso_id')->nullable();
            $table->timestamps();
        });

        DB::table('avisos')->insert([
            'id' => 15,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('anticipos_certificado')->insert([
            'id' => 22,
            'aviso_id' => 15,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $migration = include database_path('migrations/2026_04_26_000007_create_anticipo_certificado_aviso_table.php');

        $migration->up();

        $this->assertDatabaseHas('anticipo_certificado_aviso', [
            'anticipo_certificado_id' => 22,
            'aviso_id' => 15,
            'origen' => 'legacy_aviso_id',
            'estado_vinculo' => 'activo',
        ]);

        $migration->down();

        $this->assertFalse(Schema::hasTable('anticipo_certificado_aviso'));
    }
}
