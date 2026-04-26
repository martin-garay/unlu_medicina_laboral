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
            $table->string('estado')->default('inicial')->after('tipo');
        });

        DB::table('avisos')
            ->whereNull('estado')
            ->update(['estado' => 'inicial']);

        DB::table('anticipos_certificado')
            ->where('estado', 'registrado')
            ->update(['estado' => 'inicial']);

        $this->setAnticipoEstadoDefault('inicial');
    }

    public function down(): void
    {
        $this->setAnticipoEstadoDefault('registrado');

        DB::table('anticipos_certificado')
            ->where('estado', 'inicial')
            ->update(['estado' => 'registrado']);

        Schema::table('avisos', function (Blueprint $table) {
            $table->dropColumn('estado');
        });
    }

    private function setAnticipoEstadoDefault(string $default): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'pgsql') {
            DB::statement("ALTER TABLE anticipos_certificado ALTER COLUMN estado SET DEFAULT '{$default}'");

            return;
        }

        Schema::table('anticipos_certificado', function (Blueprint $table) use ($default) {
            $table->string('estado')->default($default)->change();
        });
    }
};
