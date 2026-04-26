<?php

namespace Tests\Unit\Flows\Certificado\Handlers;

use App\Flows\Certificado\Handlers\CertificadoAdjuntarOtroStepHandler;
use App\Models\Conversacion;
use App\Services\CertificadoMessageService;
use App\Services\Conversation\ConversationContextService;
use Tests\TestCase;

class CertificadoAdjuntarOtroStepHandlerTest extends TestCase
{
    public function test_yes_selection_returns_to_attachment_step(): void
    {
        $result = $this->handler()->handle($this->conversationWithAttachments(), ['text' => '1']);

        $this->assertSame('whatsapp.certificado.adjuntar_archivo', $result->messageKey);
        $this->assertSame('certificado_adjunto', $result->nextStep);
        $this->assertSame('certificado_adjunto', $result->nextState);
        $this->assertSame('certificado_attach_more_selected', $result->payload['event_name']);
    }

    public function test_no_selection_moves_to_final_confirmation(): void
    {
        $result = $this->handler()->handle($this->conversationWithAttachments(), ['text' => '2']);

        $this->assertSame(config('medicina_laboral.mensajes.templates.certificado_confirmacion_final'), $result->template);
        $this->assertSame('certificado_confirmacion_final', $result->nextStep);
        $this->assertSame(1, $result->templateData['cantidad_archivos']);
        $this->assertSame(['certificado.pdf'], $result->templateData['nombres_o_referencias_archivos']);
        $this->assertSame('certificado_attachment_selection_completed', $result->payload['event_name']);
    }

    public function test_invalid_selection_keeps_step_and_prompts_available_options(): void
    {
        $result = $this->handler()->handle($this->conversationWithAttachments(), ['text' => 'quizas']);

        $this->assertFalse($result->isValid);
        $this->assertSame('invalid_option', $result->errorCode);
        $this->assertStringContainsString(__('whatsapp.errores.invalid_option'), $result->message);
        $this->assertStringContainsString(__('whatsapp.certificado.adjuntar_otro_archivo'), $result->message);
        $this->assertSame(1, $result->incrementAttempts);
    }

    public function test_no_selection_without_attachments_returns_to_attachment_step(): void
    {
        $result = $this->handler()->handle($this->conversationWithAttachments([]), ['text' => 'no']);

        $this->assertSame('whatsapp.certificado.errores.adjunto_requerido', $result->messageKey);
        $this->assertSame('certificado_adjunto', $result->nextStep);
        $this->assertSame('certificado_adjunto', $result->nextState);
    }

    private function handler(): CertificadoAdjuntarOtroStepHandler
    {
        return new CertificadoAdjuntarOtroStepHandler(
            new ConversationContextService(),
            app(CertificadoMessageService::class),
        );
    }

    private function conversationWithAttachments(?array $attachments = null): Conversacion
    {
        $attachments ??= [
            [
                'provider_media_id' => 'media-1',
                'mime_type' => 'application/pdf',
                'filename' => 'certificado.pdf',
                'source_type' => 'document',
                'storage_status' => 'metadata_only',
            ],
        ];

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
}
