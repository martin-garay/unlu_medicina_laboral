<?php

namespace Tests\Unit\Flows\Aviso\Handlers;

use App\Flows\Aviso\Handlers\AvisoConfirmacionFinalStepHandler;
use App\Models\Conversacion;
use App\Services\Conversation\ConversationContextService;
use Tests\TestCase;

class AvisoConfirmacionFinalStepHandlerTest extends TestCase
{
    public function test_cancel_uses_aviso_cancellation_template_and_returns_to_menu(): void
    {
        $handler = new AvisoConfirmacionFinalStepHandler(new ConversationContextService());
        $conversation = new Conversacion([
            'tipo_flujo' => 'inasistencia',
            'metadata' => [
                'identificacion' => ['nombre_completo' => 'Ana'],
                'aviso' => ['motivo' => 'Fiebre'],
            ],
        ]);

        $result = $handler->handle($conversation, ['text' => 'cancelar']);

        $this->assertSame(config('medicina_laboral.mensajes.templates.aviso_cancelacion'), $result->template);
        $this->assertSame('menu_principal', $result->nextStep);
        $this->assertTrue($result->shouldShowMenu);
    }
}
