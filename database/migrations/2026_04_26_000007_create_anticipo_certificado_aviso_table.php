<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('anticipo_certificado_aviso', function (Blueprint $table) {
            $table->id();
            $table->foreignId('anticipo_certificado_id')->constrained('anticipos_certificado')->cascadeOnDelete();
            $table->foreignId('aviso_id')->constrained('avisos')->cascadeOnDelete();
            $table->string('origen')->default('conversacion');
            $table->string('estado_vinculo')->default('activo');
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['anticipo_certificado_id', 'aviso_id'], 'anticipo_certificado_aviso_unique');
            $table->index('anticipo_certificado_id', 'anticipo_certificado_aviso_anticipo_idx');
            $table->index('aviso_id', 'anticipo_certificado_aviso_aviso_idx');
            $table->index('estado_vinculo', 'anticipo_certificado_aviso_estado_idx');
        });

        $now = now();

        DB::table('anticipos_certificado')
            ->whereNotNull('aviso_id')
            ->orderBy('id')
            ->select(['id', 'aviso_id'])
            ->chunkById(100, function ($anticipos) use ($now): void {
                $rows = $anticipos->map(static fn ($anticipo): array => [
                    'anticipo_certificado_id' => $anticipo->id,
                    'aviso_id' => $anticipo->aviso_id,
                    'origen' => 'legacy_aviso_id',
                    'estado_vinculo' => 'activo',
                    'metadata' => json_encode([
                        'backfilled_from' => 'anticipos_certificado.aviso_id',
                    ]),
                    'created_at' => $now,
                    'updated_at' => $now,
                ])->all();

                DB::table('anticipo_certificado_aviso')->insertOrIgnore($rows);
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('anticipo_certificado_aviso');
    }
};
