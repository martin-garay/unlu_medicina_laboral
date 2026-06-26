<?php

namespace Tests\Unit\Flows\Aviso\Handlers;

use App\Flows\Aviso\Handlers\AvisoFechaHastaStepHandler;
use App\Flows\Validators\AvisoFechaHastaValidator;
use App\Models\Conversacion;
use App\Services\Conversation\ConversationContextService;
use Tests\TestCase;

class AvisoFechaHastaStepHandlerTest extends TestCase
{
    public function test_valid_date_moves_to_tipo_ausentismo_with_menu(): void
    {
        $handler = new AvisoFechaHastaStepHandler(
            new AvisoFechaHastaValidator(),
            new ConversationContextService(),
        );

        $conversation = new Conversacion([
            'tipo_flujo' => 'inasistencia',
            'metadata' => [
                'aviso' => [
                    'fecha_desde' => '2026-03-20',
                ],
            ],
        ]);

        $result = $handler->handle($conversation, ['text' => '21/03/2026']);

        $this->assertTrue($result->isValid);
        $this->assertSame('aviso_tipo_ausentismo', $result->nextStep);
        $this->assertSame(__('whatsapp.aviso.prompts.tipo_ausentismo'), $result->menuConfig['body_text']);
        $this->assertSame('ausentismo_por_enfermedad', $result->menuConfig['buttons'][0]['id']);
        $this->assertSame('ausentismo_familiar_enfermo', $result->menuConfig['buttons'][1]['id']);
        $this->assertSame(
            '2026-03-21',
            $result->payload['conversation_updates']['metadata']['aviso']['fecha_hasta']
        );
    }

    public function test_date_before_start_returns_specific_error_message_key(): void
    {
        $handler = new AvisoFechaHastaStepHandler(
            new AvisoFechaHastaValidator(),
            new ConversationContextService(),
        );

        $conversation = new Conversacion([
            'tipo_flujo' => 'inasistencia',
            'metadata' => [
                'aviso' => [
                    'fecha_desde' => '2026-03-20',
                ],
            ],
        ]);

        $result = $handler->handle($conversation, ['text' => '19/03/2026']);

        $this->assertFalse($result->isValid);
        $this->assertSame('before_start_date', $result->errorCode);
        $this->assertSame('whatsapp.errores.before_start_date', $result->messageKey);
    }
}
