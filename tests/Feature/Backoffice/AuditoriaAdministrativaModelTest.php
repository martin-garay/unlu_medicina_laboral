<?php

namespace Tests\Feature\Backoffice;

use App\Models\AuditoriaAdministrativa;
use App\Models\Aviso;
use App\Models\User;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AuditoriaAdministrativaModelTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        (require database_path('migrations/2026_04_27_000008_create_users_table.php'))->up();
        (require database_path('migrations/2025_01_01_000000_create_avisos_table.php'))->up();
        (require database_path('migrations/2026_04_28_230300_create_auditoria_administrativa_table.php'))->up();
    }

    public function test_migration_creates_administrative_audit_contract_columns(): void
    {
        $this->assertTrue(Schema::hasTable('auditoria_administrativa'));

        $this->assertTrue(Schema::hasColumns('auditoria_administrativa', [
            'id',
            'actor_user_id',
            'action',
            'origin',
            'auditable_type',
            'auditable_id',
            'before_values',
            'after_values',
            'metadata',
            'created_at',
        ]));

        $this->assertFalse(Schema::hasColumn('auditoria_administrativa', 'updated_at'));
    }

    public function test_it_casts_values_and_resolves_actor_and_auditable_entity(): void
    {
        $actor = User::create([
            'name' => 'Audit Admin',
            'email' => 'audit@example.test',
            'password' => 'secret',
        ]);

        $aviso = Aviso::create([
            'dni' => '12345678',
            'tipo' => 'ausencia',
            'wa_number' => '5491111111111',
        ]);

        $event = AuditoriaAdministrativa::create([
            'actor_user_id' => $actor->id,
            'action' => 'aviso.verified',
            'origin' => 'filament',
            'auditable_type' => Aviso::class,
            'auditable_id' => $aviso->id,
            'before_values' => ['estado' => 'inicial'],
            'after_values' => ['estado' => 'verificado'],
            'metadata' => ['fields' => ['estado']],
        ]);

        $this->assertSame($actor->id, $event->actor->id);
        $this->assertSame($aviso->id, $event->auditable->id);
        $this->assertSame(['estado' => 'inicial'], $event->before_values);
        $this->assertSame(['estado' => 'verificado'], $event->after_values);
        $this->assertSame(['fields' => ['estado']], $event->metadata);
    }
}
