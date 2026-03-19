<?php

namespace Tests\Unit\Flows\Certificado\Handlers;

use App\Flows\Certificado\Handlers\CertificadoAdjuntoStepHandler;
use App\Models\Conversacion;
use App\Services\CertificadoMessageService;
use App\Services\Conversation\ConversationContextService;
use App\Services\Storage\MetadataDraftAttachmentStorage;
use Tests\TestCase;

class CertificadoAdjuntoStepHandlerTest extends TestCase
{
    public function test_valid_attachment_returns_draft_summary_template_and_moves_to_pending_step(): void
    {
        $handler = new CertificadoAdjuntoStepHandler(
            new ConversationContextService(),
            app(CertificadoMessageService::class),
            new MetadataDraftAttachmentStorage(),
        );

        $conversation = new Conversacion([
            'tipo_flujo' => 'certificado',
            'metadata' => [
                'identificacion' => [
                    'nombre_completo' => 'Ana Perez',
                    'legajo' => '123',
                ],
                'certificado' => [
                    'numero_aviso' => 'AV-15',
                    'tipo_certificado_label' => 'Electrónico',
                    'adjuntos' => [],
                ],
            ],
        ]);

        $result = $handler->handle($conversation, [
            'incoming_message_type' => 'document',
            'media' => [
                'provider_media_id' => 'media-1',
                'mime_type' => 'application/pdf',
                'filename' => 'certificado.pdf',
                'source_type' => 'document',
            ],
        ]);

        $this->assertSame(config('medicina_laboral.mensajes.templates.certificado_resumen_borrador'), $result->template);
        $this->assertSame('certificado_confirmacion_pendiente', $result->nextStep);
        $this->assertSame(1, $result->templateData['cantidad_archivos']);
        $this->assertSame(['certificado.pdf'], $result->templateData['nombres_o_referencias_archivos']);
        $this->assertSame(
            'metadata_only',
            $result->payload['conversation_updates']['metadata']['certificado']['adjuntos'][0]['storage_status']
        );
    }
}
