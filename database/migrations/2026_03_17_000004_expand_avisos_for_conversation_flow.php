<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('avisos', function (Blueprint $table) {
            $table->foreignId('conversacion_id')->nullable()->after('id')->constrained('conversaciones')->nullOnDelete();
            $table->string('nombre_completo')->nullable()->after('dni');
            $table->string('legajo')->nullable()->after('nombre_completo');
            $table->string('sede')->nullable()->after('legajo');
            $table->string('jornada_laboral')->nullable()->after('sede');
            $table->date('fecha_fin')->nullable()->after('fecha_inicio');
            $table->string('tipo_ausentismo')->nullable()->after('tipo');
            $table->text('motivo')->nullable()->after('tipo_ausentismo');
            $table->string('domicilio_circunstancial')->nullable()->after('motivo');
            $table->text('observaciones')->nullable()->after('domicilio_circunstancial');
            $table->json('metadata')->nullable()->after('wa_number');
        });

        DB::statement('ALTER TABLE avisos ALTER COLUMN dni DROP NOT NULL');
    }

    public function down(): void
    {
        Schema::table('avisos', function (Blueprint $table) {
            $table->dropConstrainedForeignId('conversacion_id');
            $table->dropColumn([
                'nombre_completo',
                'legajo',
                'sede',
                'jornada_laboral',
                'fecha_fin',
                'tipo_ausentismo',
                'motivo',
                'domicilio_circunstancial',
                'observaciones',
                'metadata',
            ]);
        });

        DB::statement("UPDATE avisos SET dni = '' WHERE dni IS NULL");
        DB::statement('ALTER TABLE avisos ALTER COLUMN dni SET NOT NULL');
    }
};
