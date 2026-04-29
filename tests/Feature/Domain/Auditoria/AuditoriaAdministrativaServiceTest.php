<?php

namespace Tests\Feature\Domain\Auditoria;

use App\Domain\Auditoria\Services\AuditoriaAdministrativaService;
use App\Models\AuditoriaAdministrativa;
use App\Models\Aviso;
use App\Models\User;
use InvalidArgumentException;
use Tests\TestCase;

class AuditoriaAdministrativaServiceTest extends TestCase
{
    private AuditoriaAdministrativaService $service;

    protected function setUp(): void
    {
        parent::setUp();

        (require database_path('migrations/2026_04_27_000008_create_users_table.php'))->up();
        (require database_path('migrations/2025_01_01_000000_create_avisos_table.php'))->up();
        (require database_path('migrations/2026_04_28_230300_create_auditoria_administrativa_table.php'))->up();

        $this->service = new AuditoriaAdministrativaService();
    }

    public function test_records_administrative_audit_event_with_actor_and_auditable_entity(): void
    {
        $actor = User::create([
            'name' => 'Audit Admin',
            'email' => 'audit-service@example.test',
            'password' => 'secret',
        ]);

        $aviso = Aviso::create([
            'dni' => '12345678',
            'tipo' => 'ausencia',
            'wa_number' => '5491111111111',
        ]);

        $event = $this->service->record(
            action: 'aviso.verified',
            origin: AuditoriaAdministrativaService::ORIGIN_FILAMENT,
            actor: $actor,
            auditable: $aviso,
            beforeValues: ['estado' => 'inicial'],
            afterValues: ['estado' => 'verificado'],
            metadata: ['reason_code' => 'manual_review'],
        );

        $this->assertInstanceOf(AuditoriaAdministrativa::class, $event);
        $this->assertDatabaseHas('auditoria_administrativa', [
            'id' => $event->id,
            'actor_user_id' => $actor->id,
            'action' => 'aviso.verified',
            'origin' => 'filament',
            'auditable_type' => Aviso::class,
            'auditable_id' => $aviso->id,
        ]);
        $this->assertSame(['estado' => 'inicial'], $event->before_values);
        $this->assertSame(['estado' => 'verificado'], $event->after_values);
        $this->assertSame(['reason_code' => 'manual_review'], $event->metadata);
    }

    public function test_records_system_event_without_actor_or_auditable_entity(): void
    {
        $event = $this->service->record(
            action: 'permissions.seeded',
            origin: AuditoriaAdministrativaService::ORIGIN_COMMAND,
        );

        $this->assertNull($event->actor_user_id);
        $this->assertNull($event->auditable_type);
        $this->assertNull($event->auditable_id);
        $this->assertNull($event->before_values);
        $this->assertNull($event->after_values);
        $this->assertNull($event->metadata);
    }

    public function test_rejects_empty_action(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->service->record(
            action: '   ',
            origin: AuditoriaAdministrativaService::ORIGIN_SYSTEM,
        );
    }

    public function test_rejects_unsupported_origin(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->service->record(
            action: 'aviso.verified',
            origin: 'api',
        );
    }
}
