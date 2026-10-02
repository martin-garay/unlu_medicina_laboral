<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('anticipo_certificado_archivos', function (Blueprint $t) {
            $t->unsignedBigInteger('anticipo_certificado_id')->nullable()->change();
            $t->uuid('intento_uuid')->nullable()->index();
            $t->string('provider_message_id')->nullable();
            $t->string('estado_storage')->default('metadata_only')->index();
            $t->json('politica')->nullable();
            $t->unique(['conversacion_id', 'intento_uuid', 'provider_message_id'], 'certificado_origen_unique');
        });
        Schema::create('anticipo_certificado_archivo_contenidos', function (Blueprint $t) {
            $t->foreignId('archivo_id')->primary()->constrained('anticipo_certificado_archivos')->cascadeOnDelete();
            $t->binary('contenido');
        });
        Schema::create('jobs', function (Blueprint $t) {
            $t->bigIncrements('id');
            $t->string('queue')->index();
            $t->longText('payload');
            $t->unsignedTinyInteger('attempts');
            $t->unsignedInteger('reserved_at')->nullable();
            $t->unsignedInteger('available_at');
            $t->unsignedInteger('created_at');
        });
        Schema::create('failed_jobs', function (Blueprint $t) {
            $t->id();
            $t->string('uuid')->unique();
            $t->text('connection');
            $t->text('queue');
            $t->longText('payload');
            $t->longText('exception');
            $t->timestamp('failed_at')->useCurrent();
        });
    }

    public function down(): void
    {
        // Keep nullable anticipo FK: drafts cannot be made non-null without data loss.
        Schema::dropIfExists('failed_jobs');
        Schema::dropIfExists('jobs');
        Schema::dropIfExists('anticipo_certificado_archivo_contenidos');
        Schema::table('anticipo_certificado_archivos', function (Blueprint $t) {
            $t->dropUnique('certificado_origen_unique');
            $t->dropColumn(['intento_uuid', 'provider_message_id', 'estado_storage', 'politica']);
        });
    }
};
