<?php

namespace Tests\Feature\Services;

use App\Services\AvisoService;
use App\Services\ConversationManager;
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
    }
}
