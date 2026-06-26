<?php

namespace Tests\Unit\Flows\Certificado\Handlers;

use App\Flows\Certificado\Handlers\CertificadoAdjuntoStepHandler;
use App\Models\Conversacion;
use App\Services\Conversation\ConversationContextService;
use App\Services\CertificadoMessageService;
use App\Services\Storage\MetadataDraftAttachmentStorage;
use Tests\TestCase;

class CertificadoAdjuntoStepHandlerTest extends TestCase
{
    public function test_valid_attachment_prompts_to_add_another_file_when_limit_is_not_reached(): void
    {
        $handler = $this->handler();
        $conversation = $this->conversationWithAttachments();

        $result = $handler->handle($conversation, $this->documentInput('media-1', 'certificado.pdf'));

        $this->assertSame('certificado_adjuntar_otro', $result->nextStep);
        $this->assertSame('certificado_adjuntar_otro', $result->nextState);
        $this->assertNull($result->template);
        $this->assertSame(__('whatsapp.certificado.adjuntar_otro_archivo'), $result->menuConfig['body_text']);
        $this->assertSame('adjuntar_otro_si', $result->menuConfig['buttons'][0]['id']);
        $this->assertSame('adjuntar_otro_no', $result->menuConfig['buttons'][1]['id']);
        $this->assertSame(
            'metadata_only',
            $result->payload['conversation_updates']['metadata']['certificado']['adjuntos'][0]['storage_status']
        );
        $this->assertSame(1, $result->payload['event_metadata']['attachments_count']);
    }

    public function test_valid_attachment_moves_to_final_confirmation_when_limit_is_reached(): void
    {
        $handler = $this->handler();
        $conversation = $this->conversationWithAttachments([
            ['provider_media_id' => 'media-1', 'filename' => 'certificado-1.pdf'],
            ['provider_media_id' => 'media-2', 'filename' => 'certificado-2.pdf'],
        ]);

        $result = $handler->handle($conversation, $this->documentInput('media-3', 'certificado-3.pdf'));

        $this->assertSame(config('medicina_laboral.mensajes.templates.certificado_confirmacion_final'), $result->template);
        $this->assertSame('certificado_confirmacion_final', $result->nextStep);
        $this->assertSame(3, $result->templateData['cantidad_archivos']);
        $this->assertSame(
            ['certificado-1.pdf', 'certificado-2.pdf', 'certificado-3.pdf'],
            $result->templateData['nombres_o_referencias_archivos']
        );
        $this->assertSame(3, $result->payload['event_metadata']['attachments_count']);
        $this->assertTrue($result->payload['event_metadata']['max_files_reached']);
    }

    public function test_attachment_is_rejected_when_limit_was_already_reached(): void
    {
        $handler = $this->handler();
        $conversation = $this->conversationWithAttachments([
            ['provider_media_id' => 'media-1', 'filename' => 'certificado-1.pdf'],
            ['provider_media_id' => 'media-2', 'filename' => 'certificado-2.pdf'],
            ['provider_media_id' => 'media-3', 'filename' => 'certificado-3.pdf'],
        ]);

        $result = $handler->handle($conversation, $this->documentInput('media-4', 'certificado-4.pdf'));

        $this->assertFalse($result->isValid);
        $this->assertSame('attachment_limit_exceeded', $result->errorCode);
        $this->assertSame('whatsapp.certificado.errores.max_archivos', $result->messageKey);
        $this->assertSame(1, $result->incrementAttempts);
    }

    private function handler(): CertificadoAdjuntoStepHandler
    {
        return new CertificadoAdjuntoStepHandler(
            new ConversationContextService(),
            app(CertificadoMessageService::class),
            new MetadataDraftAttachmentStorage(),
        );
    }

    private function conversationWithAttachments(array $attachments = []): Conversacion
    {
        return new Conversacion([
            'tipo_flujo' => 'certificado',
            'metadata' => [
                'identificacion' => [
                    'nombre_completo' => 'Ana Perez',
                    'legajo' => '123',
                ],
                'certificado' => [
                    'numero_aviso' => 'AV-15',
                    'tipo_certificado_label' => 'Electrónico',
                    'adjuntos' => $attachments,
                ],
            ],
        ]);
    }

    private function documentInput(string $mediaId, string $filename): array
    {
        return [
            'incoming_message_type' => 'document',
            'media' => [
                'provider_media_id' => $mediaId,
                'mime_type' => 'application/pdf',
                'filename' => $filename,
                'source_type' => 'document',
            ],
        ];
    }
}
