<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('anticipos_certificado', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('numero_anticipo')->nullable()->unique();
            $table->foreignId('conversacion_id')->constrained('conversaciones')->cascadeOnDelete();
            $table->foreignId('aviso_id')->constrained('avisos')->cascadeOnDelete();
            $table->string('wa_number')->nullable();
            $table->string('nombre_completo')->nullable();
            $table->string('legajo')->nullable();
            $table->string('sede')->nullable();
            $table->string('jornada_laboral')->nullable();
            $table->string('tipo_certificado');
            $table->string('estado')->default('registrado');
            $table->text('observaciones')->nullable();
            $table->timestamp('registrado_en')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index('aviso_id');
            $table->index('legajo');
            $table->index('wa_number');
            $table->index('estado');
            $table->index('conversacion_id');
        });

        Schema::create('anticipo_certificado_archivos', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('anticipo_certificado_id')->constrained('anticipos_certificado')->cascadeOnDelete();
            $table->foreignId('conversacion_id')->constrained('conversaciones')->cascadeOnDelete();
            $table->string('provider_file_id')->nullable();
            $table->string('nombre_original')->nullable();
            $table->string('mime_type')->nullable();
            $table->string('extension')->nullable();
            $table->unsignedBigInteger('size_bytes')->nullable();
            $table->string('storage_disk')->nullable();
            $table->string('storage_path')->nullable();
            $table->string('hash_archivo')->nullable();
            $table->string('estado_validacion')->default('aceptado');
            $table->string('motivo_rechazo')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index('anticipo_certificado_id');
            $table->index('conversacion_id');
            $table->index('estado_validacion');
        });

        Schema::table('conversaciones', function (Blueprint $table) {
            $table->foreignId('anticipo_certificado_id')
                ->nullable()
                ->after('aviso_id')
                ->constrained('anticipos_certificado')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('conversaciones', function (Blueprint $table) {
            $table->dropConstrainedForeignId('anticipo_certificado_id');
        });

        Schema::dropIfExists('anticipo_certificado_archivos');
        Schema::dropIfExists('anticipos_certificado');
    }
};
