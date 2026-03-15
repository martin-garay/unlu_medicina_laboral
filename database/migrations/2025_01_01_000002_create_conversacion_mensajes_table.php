<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('conversacion_mensajes', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('conversacion_id')->constrained('conversaciones')->cascadeOnDelete();
            $table->string('direccion', 10);
            $table->string('provider_message_id')->nullable();
            $table->string('tipo_mensaje')->default('text');
            $table->string('step_key')->nullable();
            $table->text('contenido_texto')->nullable();
            $table->boolean('es_valido')->nullable();
            $table->string('motivo_invalidez')->nullable();
            $table->string('message_key')->nullable();
            $table->string('template_name')->nullable();
            $table->json('payload_crudo')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index('conversacion_id');
            $table->index('provider_message_id');
            $table->index('step_key');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conversacion_mensajes');
    }
};
