<?php

namespace Tests\Unit\Flows\Placeholders\Handlers;

use App\Flows\Placeholders\Handlers\CertificadoConfirmacionPendienteStepHandler;
use App\Models\Conversacion;
use App\Services\AnticipoCertificadoService;
use App\Services\Conversation\ConversationContextService;
use Tests\TestCase;

class CertificadoConfirmacionPendienteStepHandlerTest extends TestCase
{
    public function test_it_returns_draft_summary_template_when_flow_is_pending(): void
    {
        $handler = new CertificadoConfirmacionPendienteStepHandler(
            new ConversationContextService(),
            app(AnticipoCertificadoService::class),
        );

        $conversation = new Conversacion([
            'tipo_flujo' => 'certificado',
            'metadata' => [
                'identificacion' => [
                    'nombre_completo' => 'Ana Perez',
                    'legajo' => '123',
                ],
                'certificado' => [
                    'numero_aviso' => 'AV-8',
                    'tipo_certificado_label' => 'Manuscrito',
                    'adjuntos' => [
                        ['filename' => 'certificado.pdf'],
                    ],
                ],
            ],
        ]);

        $result = $handler->handle($conversation, ['text' => 'ok']);

        $this->assertSame(config('medicina_laboral.mensajes.templates.certificado_confirmacion_final'), $result->template);
        $this->assertSame('AV-8', $result->templateData['numero_aviso']);
        $this->assertSame(1, $result->templateData['cantidad_archivos']);
        $this->assertSame('certificado_confirmacion_final', $result->nextStep);
    }

    public function test_cancel_uses_certificado_cancellation_template_and_returns_to_menu(): void
    {
        $handler = new CertificadoConfirmacionPendienteStepHandler(
            new ConversationContextService(),
            app(AnticipoCertificadoService::class),
        );

        $conversation = new Conversacion([
            'tipo_flujo' => 'certificado',
            'metadata' => [],
        ]);

        $result = $handler->handle($conversation, ['text' => 'cancelar']);

        $this->assertSame(config('medicina_laboral.mensajes.templates.certificado_cancelacion'), $result->template);
        $this->assertSame('menu_principal', $result->nextStep);
        $this->assertTrue($result->shouldShowMenu);
    }
}
