<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('auditoria_administrativa', function (Blueprint $table) {
            $table->id();
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action');
            $table->string('origin');
            $table->nullableMorphs('auditable', 'auditoria_admin_auditable_idx');
            $table->json('before_values')->nullable();
            $table->json('after_values')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index('action', 'auditoria_admin_action_idx');
            $table->index('origin', 'auditoria_admin_origin_idx');
            $table->index('created_at', 'auditoria_admin_created_at_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('auditoria_administrativa');
    }
};
