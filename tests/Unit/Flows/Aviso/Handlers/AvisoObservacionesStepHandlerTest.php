<?php

namespace Tests\Unit\Flows\Aviso\Handlers;

use App\Flows\Aviso\Handlers\AvisoObservacionesStepHandler;
use App\Models\Conversacion;
use App\Services\AvisoService;
use App\Services\Conversation\ConversationContextService;
use App\Services\Notifications\NullBusinessNotificationSender;
use Tests\TestCase;

class AvisoObservacionesStepHandlerTest extends TestCase
{
    public function test_blank_observaciones_returns_required_error(): void
    {
        $handler = new AvisoObservacionesStepHandler(
            new ConversationContextService(),
            new AvisoService(new NullBusinessNotificationSender()),
        );

        $result = $handler->handle(new Conversacion(['tipo_flujo' => 'inasistencia']), ['text' => '   ']);

        $this->assertFalse($result->isValid);
        $this->assertSame('required', $result->errorCode);
    }

    public function test_skip_keyword_moves_to_confirmation_and_stores_null_observaciones(): void
    {
        $handler = new AvisoObservacionesStepHandler(
            new ConversationContextService(),
            new AvisoService(new NullBusinessNotificationSender()),
        );

        $conversation = new Conversacion([
            'tipo_flujo' => 'inasistencia',
            'metadata' => [
                'identificacion' => [
                    'nombre_completo' => 'Ana Perez',
                    'legajo' => '10001',
                    'sede' => 'Sede Central',
                    'jornada_laboral' => 'Manana',
                ],
                'aviso' => [
                    'fecha_desde' => '2026-03-20',
                    'fecha_hasta' => '2026-03-21',
                    'tipo_ausentismo' => 'por_enfermedad',
                    'tipo_ausentismo_label' => 'Por Enfermedad',
                    'motivo' => 'Fiebre',
                    'requiere_datos_familiar' => false,
                ],
            ],
        ]);

        $result = $handler->handle($conversation, ['text' => 'sin observaciones']);

        $this->assertTrue($result->isValid);
        $this->assertSame('aviso_confirmacion_final', $result->nextStep);
        $this->assertSame(
            config('medicina_laboral.mensajes.templates.aviso_confirmacion_final'),
            $result->template
        );
        $this->assertNull($result->payload['conversation_updates']['metadata']['aviso']['observaciones']);
        $this->assertNull($result->templateData['observaciones']);
    }

    public function test_requires_familiar_routes_to_placeholder_step(): void
    {
        $handler = new AvisoObservacionesStepHandler(
            new ConversationContextService(),
            new AvisoService(new NullBusinessNotificationSender()),
        );

        $conversation = new Conversacion([
            'tipo_flujo' => 'inasistencia',
            'metadata' => [
                'aviso' => [
                    'requiere_datos_familiar' => true,
                ],
            ],
        ]);

        $result = $handler->handle($conversation, ['text' => 'Necesita acompanamiento']);

        $this->assertTrue($result->isValid);
        $this->assertSame('aviso_familiar_pendiente', $result->nextStep);
        $this->assertSame(
            'Necesita acompanamiento',
            $result->payload['conversation_updates']['metadata']['aviso']['observaciones']
        );
        $this->assertSame('aviso_partial_flow_completed', $result->payload['event_name']);
    }
}
