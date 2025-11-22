<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('avisos', function (Blueprint $table) {
            $table->id();
            $table->string('dni');
            $table->string('tipo');
            $table->date('fecha_inicio')->nullable();
            $table->integer('cantidad_dias')->nullable();
            $table->text('certificado_base64')->nullable();
            $table->string('wa_number');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('avisos');
    }
};
