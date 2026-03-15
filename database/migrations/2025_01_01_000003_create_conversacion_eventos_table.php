<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('conversacion_eventos', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('conversacion_id')->constrained('conversaciones')->cascadeOnDelete();
            $table->string('tipo_evento');
            $table->string('step_key')->nullable();
            $table->text('descripcion')->nullable();
            $table->string('codigo')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index('conversacion_id');
            $table->index('tipo_evento');
            $table->index('step_key');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conversacion_eventos');
    }
};
