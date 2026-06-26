<?php

namespace Tests\Unit\Flows\Certificado\Handlers;

use App\Flows\Certificado\Handlers\CertificadoTipoStepHandler;
use App\Flows\Validators\TipoCertificadoValidator;
use App\Models\Conversacion;
use App\Services\Conversation\ConversationContextService;
use Tests\TestCase;

class CertificadoTipoStepHandlerTest extends TestCase
{
    public function test_valid_option_stores_tipo_and_moves_to_adjunto_step(): void
    {
        $handler = new CertificadoTipoStepHandler(
            new TipoCertificadoValidator(),
            new ConversationContextService(),
        );

        $conversation = new Conversacion([
            'tipo_flujo' => 'certificado',
            'metadata' => [
                'certificado' => [
                    'aviso_id' => 15,
                ],
            ],
        ]);

        $result = $handler->handle($conversation, ['text' => '2']);

        $this->assertTrue($result->isValid);
        $this->assertSame('certificado_adjunto', $result->nextStep);
        $this->assertSame(
            'electronico',
            $result->payload['conversation_updates']['metadata']['certificado']['tipo_certificado']
        );
        $this->assertSame(
            'Electrónico',
            $result->payload['conversation_updates']['metadata']['certificado']['tipo_certificado_label']
        );
        $this->assertSame([], $result->payload['conversation_updates']['metadata']['certificado']['adjuntos']);
    }

    public function test_accepts_button_id_selection(): void
    {
        $handler = new CertificadoTipoStepHandler(
            new TipoCertificadoValidator(),
            new ConversationContextService(),
        );

        $result = $handler->handle(new Conversacion(['tipo_flujo' => 'certificado']), [
            'button_id' => 'certificado_electronico',
        ]);

        $this->assertTrue($result->isValid);
        $this->assertSame('electronico', $result->payload['conversation_updates']['metadata']['certificado']['tipo_certificado']);
    }

    public function test_invalid_option_returns_catalog_menu(): void
    {
        $handler = new CertificadoTipoStepHandler(
            new TipoCertificadoValidator(),
            new ConversationContextService(),
        );

        $result = $handler->handle(new Conversacion(['tipo_flujo' => 'certificado']), ['text' => '9']);

        $this->assertFalse($result->isValid);
        $this->assertSame('invalid_option', $result->errorCode);
        $this->assertSame(__('whatsapp.certificado.tipo_certificado'), $result->menuConfig['body_text']);
        $this->assertSame('certificado_manuscrito', $result->menuConfig['buttons'][0]['id']);
        $this->assertSame('certificado_electronico', $result->menuConfig['buttons'][1]['id']);
    }

    public function test_cancel_returns_to_main_menu_and_resets_certificado_context(): void
    {
        $handler = new CertificadoTipoStepHandler(
            new TipoCertificadoValidator(),
            new ConversationContextService(),
        );

        $conversation = new Conversacion([
            'tipo_flujo' => 'certificado',
            'metadata' => [
                'identificacion' => ['legajo' => '10001'],
                'certificado' => ['aviso_id' => 15, 'tipo_certificado' => 'manuscrito'],
            ],
        ]);

        $result = $handler->handle($conversation, ['text' => 'cancelar']);

        $this->assertTrue($result->isValid);
        $this->assertSame('menu_principal', $result->nextStep);
        $this->assertTrue($result->shouldShowMenu);
        $this->assertSame([], $result->payload['conversation_updates']['metadata']['certificado']);
    }
}
