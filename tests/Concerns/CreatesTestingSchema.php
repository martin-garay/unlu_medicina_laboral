<?php

namespace Tests\Concerns;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

trait CreatesTestingSchema
{
    protected function createTestingSchema(): void
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('conversacion_eventos');
        Schema::dropIfExists('conversacion_mensajes');
        Schema::dropIfExists('conversaciones');
        Schema::dropIfExists('avisos');
        Schema::enableForeignKeyConstraints();

        Schema::create('conversaciones', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->nullable();
            $table->string('wa_number')->nullable();
            $table->string('canal')->nullable();
            $table->string('tipo_flujo')->nullable();
            $table->string('estado_actual')->nullable();
            $table->string('paso_actual')->nullable();
            $table->boolean('activa')->default(true);
            $table->integer('cantidad_mensajes_recibidos')->default(0);
            $table->integer('cantidad_mensajes_enviados')->default(0);
            $table->integer('cantidad_mensajes_validos')->default(0);
            $table->integer('cantidad_mensajes_invalidos')->default(0);
            $table->integer('cantidad_intentos_actual')->default(0);
            $table->integer('cantidad_intentos_totales')->default(0);
            $table->timestamp('ultimo_mensaje_recibido_en')->nullable();
            $table->timestamp('ultimo_mensaje_enviado_en')->nullable();
            $table->timestamp('primer_umbral_notificado_en')->nullable();
            $table->timestamp('segundo_umbral_notificado_en')->nullable();
            $table->timestamp('expira_en')->nullable();
            $table->timestamp('finalizada_en')->nullable();
            $table->string('motivo_finalizacion')->nullable();
            $table->unsignedBigInteger('aviso_id')->nullable();
            $table->json('metadata')->nullable();
            $table->string('estado')->nullable();
            $table->string('tipo')->nullable();
            $table->string('dni')->nullable();
            $table->timestamps();
        });

        Schema::create('avisos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('conversacion_id')->nullable();
            $table->string('dni')->nullable();
            $table->string('nombre_completo')->nullable();
            $table->string('legajo')->nullable();
            $table->string('sede')->nullable();
            $table->string('jornada_laboral')->nullable();
            $table->string('tipo');
            $table->string('tipo_ausentismo')->nullable();
            $table->date('fecha_inicio')->nullable();
            $table->date('fecha_fin')->nullable();
            $table->integer('cantidad_dias')->nullable();
            $table->text('certificado_base64')->nullable();
            $table->text('motivo')->nullable();
            $table->string('domicilio_circunstancial')->nullable();
            $table->text('observaciones')->nullable();
            $table->string('wa_number')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('conversacion_mensajes', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid');
            $table->unsignedBigInteger('conversacion_id');
            $table->string('direccion');
            $table->string('provider_message_id')->nullable();
            $table->string('tipo_mensaje')->nullable();
            $table->string('step_key')->nullable();
            $table->text('contenido_texto')->nullable();
            $table->boolean('es_valido')->nullable();
            $table->string('motivo_invalidez')->nullable();
            $table->string('message_key')->nullable();
            $table->string('template_name')->nullable();
            $table->json('payload_crudo')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('conversacion_eventos', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid');
            $table->unsignedBigInteger('conversacion_id');
            $table->string('tipo_evento');
            $table->string('step_key')->nullable();
            $table->text('descripcion')->nullable();
            $table->string('codigo')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }
}
