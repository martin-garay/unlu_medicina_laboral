<?php

namespace Tests\Feature\Services;

use App\Services\AvisoService;
use App\Services\ConversationManager;
use App\Services\Notifications\Contracts\BusinessNotificationSender;
use Tests\Concerns\CreatesTestingSchema;
use Tests\TestCase;

class AvisoServiceTest extends TestCase
{
    use CreatesTestingSchema;

    protected function setUp(): void
    {
        parent::setUp();
        $this->createTestingSchema();
    }

    public function test_build_confirmation_template_data_maps_conversation_metadata(): void
    {
        $conversation = new \App\Models\Conversacion([
            'metadata' => [
                'identificacion' => [
                    'nombre_completo' => 'Ana Perez',
                    'legajo' => '123',
                    'sede' => 'Sede Central',
                    'jornada_laboral' => 'Manana',
                ],
                'aviso' => [
                    'fecha_desde' => '2026-03-19',
                    'fecha_hasta' => '2026-03-21',
                    'tipo_ausentismo' => 'por_enfermedad',
                    'tipo_ausentismo_label' => 'Por Enfermedad',
                    'motivo' => 'Fiebre',
                    'observaciones' => 'Reposo',
                ],
            ],
        ]);

        $data = app(AvisoService::class)->buildConfirmationTemplateData($conversation);

        $this->assertSame('Ana Perez', $data['nombre']);
        $this->assertSame('123', $data['legajo']);
        $this->assertSame(3, $data['dias']);
        $this->assertSame('Por Enfermedad', $data['tipo_ausentismo']);
        $this->assertSame('19/03/2026', $data['fecha_desde']);
        $this->assertSame('21/03/2026', $data['fecha_hasta']);
    }

    public function test_build_confirmation_step_result_uses_confirmation_template(): void
    {
        $conversation = new \App\Models\Conversacion([
            'metadata' => [
                'identificacion' => [
                    'nombre_completo' => 'Ana Perez',
                    'legajo' => '123',
                ],
                'aviso' => [
                    'fecha_desde' => '2026-03-19',
                    'fecha_hasta' => '2026-03-19',
                ],
            ],
        ]);

        $result = app(AvisoService::class)->buildConfirmationStepResult($conversation);

        $this->assertSame(config('medicina_laboral.mensajes.templates.aviso_confirmacion_final'), $result->template);
        $this->assertSame('Ana Perez', $result->templateData['nombre']);
    }

    public function test_create_from_conversation_persists_aviso_with_snapshot_metadata(): void
    {
        $conversation = app(ConversationManager::class)->createConversation('5491111111111', [
            'metadata' => [
                'identificacion' => [
                    'nombre_completo' => 'Ana Perez',
                    'legajo' => '123',
                    'sede' => 'Sede Central',
                    'jornada_laboral' => 'Manana',
                ],
                'aviso' => [
                    'fecha_desde' => '2026-03-19',
                    'fecha_hasta' => '2026-03-20',
                    'tipo_ausentismo' => 'por_enfermedad',
                    'motivo' => 'Fiebre',
                ],
            ],
            'tipo_flujo' => 'inasistencia',
        ]);

        $aviso = app(AvisoService::class)->createFromConversation($conversation);

        $this->assertDatabaseHas('avisos', [
            'id' => $aviso->id,
            'conversacion_id' => $conversation->id,
            'tipo' => 'inasistencia',
            'legajo' => '123',
            'cantidad_dias' => 2,
        ]);
        $this->assertSame('Ana Perez', $aviso->metadata['identificacion']['nombre_completo']);
        $this->assertSame('Fiebre', $aviso->metadata['aviso']['motivo']);
    }

    public function test_create_from_conversation_notifies_through_configured_business_sender(): void
    {
        $conversation = app(ConversationManager::class)->createConversation('5491111111111', [
            'metadata' => [
                'identificacion' => [
                    'nombre_completo' => 'Ana Perez',
                    'legajo' => '123',
                ],
                'aviso' => [
                    'fecha_desde' => '2026-03-19',
                    'fecha_hasta' => '2026-03-19',
                    'tipo_ausentismo' => 'por_enfermedad',
                ],
            ],
            'tipo_flujo' => 'inasistencia',
        ]);

        $spy = new class implements BusinessNotificationSender
        {
            public ?int $lastAvisoId = null;

            public function sendAvisoRegistered(\App\Models\Aviso $aviso): void
            {
                $this->lastAvisoId = $aviso->id;
            }
        };

        $service = new AvisoService($spy);

        $aviso = $service->createFromConversation($conversation);

        $this->assertSame($aviso->id, $spy->lastAvisoId);
    }

    public function test_build_registered_step_result_uses_registered_template(): void
    {
        $conversation = app(ConversationManager::class)->createConversation('5491111111111');

        $aviso = \App\Models\Aviso::create([
            'conversacion_id' => $conversation->id,
            'tipo' => 'inasistencia',
            'fecha_inicio' => '2026-03-19',
            'fecha_fin' => '2026-03-20',
            'cantidad_dias' => 2,
            'nombre_completo' => 'Ana Perez',
            'legajo' => '123',
            'motivo' => 'Fiebre',
            'wa_number' => '5491111111111',
        ]);

        $result = app(AvisoService::class)->buildRegisteredStepResult($aviso);

        $this->assertSame(config('medicina_laboral.mensajes.templates.aviso_registrado'), $result->template);
        $this->assertSame('AV-' . $aviso->id, $result->templateData['numero_aviso']);
        $this->assertSame(24, $result->templateData['deadline_horas']);
    }
}
