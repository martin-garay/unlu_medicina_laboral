<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('conversaciones', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('wa_number');
            $table->string('canal')->default('whatsapp');
            $table->string('tipo_flujo')->nullable();
            $table->string('estado_actual')->default('iniciada');
            $table->string('paso_actual')->nullable();
            $table->boolean('activa')->default(true);
            $table->unsignedInteger('cantidad_mensajes_recibidos')->default(0);
            $table->unsignedInteger('cantidad_mensajes_enviados')->default(0);
            $table->unsignedInteger('cantidad_mensajes_validos')->default(0);
            $table->unsignedInteger('cantidad_mensajes_invalidos')->default(0);
            $table->unsignedInteger('cantidad_intentos_actual')->default(0);
            $table->unsignedInteger('cantidad_intentos_totales')->default(0);
            $table->timestamp('ultimo_mensaje_recibido_en')->nullable();
            $table->timestamp('ultimo_mensaje_enviado_en')->nullable();
            $table->timestamp('primer_umbral_notificado_en')->nullable();
            $table->timestamp('segundo_umbral_notificado_en')->nullable();
            $table->timestamp('expira_en')->nullable();
            $table->timestamp('finalizada_en')->nullable();
            $table->string('motivo_finalizacion')->nullable();
            $table->foreignId('aviso_id')->nullable()->constrained('avisos')->nullOnDelete();
            $table->string('estado')->default('esperando_dni');
            $table->string('dni')->nullable();
            $table->string('tipo')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index('wa_number');
            $table->index('activa');
            $table->index('tipo_flujo');
            $table->index('estado_actual');
            $table->index('paso_actual');
            $table->index('ultimo_mensaje_recibido_en');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conversaciones');
    }
};
