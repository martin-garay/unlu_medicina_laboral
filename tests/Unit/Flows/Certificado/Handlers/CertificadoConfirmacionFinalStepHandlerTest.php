<?php

namespace Tests\Unit\Flows\Certificado\Handlers;

use App\Flows\Certificado\Handlers\CertificadoConfirmacionFinalStepHandler;
use App\Models\Conversacion;
use App\Services\Conversation\ConversationContextService;
use Tests\TestCase;

class CertificadoConfirmacionFinalStepHandlerTest extends TestCase
{
    public function test_confirm_marks_flow_to_finish_with_anticipo_business_action(): void
    {
        $handler = new CertificadoConfirmacionFinalStepHandler(
            new ConversationContextService(),
        );

        $result = $handler->handle(new Conversacion(['tipo_flujo' => 'certificado']), ['text' => '1']);

        $this->assertTrue($result->shouldFinish);
        $this->assertSame('create_anticipo_certificado_from_conversation', $result->payload['business_action']);
        $this->assertSame('anticipo_confirmation_accepted', $result->payload['event_name']);
    }

    public function test_cancel_returns_to_menu_without_creating_anticipo(): void
    {
        $handler = new CertificadoConfirmacionFinalStepHandler(
            new ConversationContextService(),
        );

        $result = $handler->handle(new Conversacion(['tipo_flujo' => 'certificado']), ['text' => 'cancelar']);

        $this->assertSame(config('medicina_laboral.mensajes.templates.certificado_cancelacion'), $result->template);
        $this->assertSame('menu_principal', $result->nextStep);
        $this->assertTrue($result->shouldShowMenu);
    }
}
