<?php

namespace Tests\Unit\Flows\Aviso\Handlers;

use App\Flows\Aviso\Handlers\AvisoFechaDesdeStepHandler;
use App\Flows\Validators\DateInputValidator;
use App\Models\Conversacion;
use App\Services\Conversation\ConversationContextService;
use Tests\TestCase;

class AvisoFechaDesdeStepHandlerTest extends TestCase
{
    public function test_valid_date_moves_to_fecha_hasta_and_stores_fecha_desde(): void
    {
        $handler = new AvisoFechaDesdeStepHandler(
            new DateInputValidator(),
            new ConversationContextService(),
        );

        $result = $handler->handle(new Conversacion(['tipo_flujo' => 'inasistencia']), ['text' => '20/03/2026']);

        $this->assertTrue($result->isValid);
        $this->assertSame('aviso_fecha_hasta', $result->nextStep);
        $this->assertSame(
            '2026-03-20',
            $result->payload['conversation_updates']['metadata']['aviso']['fecha_desde']
        );
        $this->assertSame('dd/mm/YYYY', $result->messageParams['date_format']);
    }

    public function test_invalid_date_returns_validation_error(): void
    {
        $handler = new AvisoFechaDesdeStepHandler(
            new DateInputValidator(),
            new ConversationContextService(),
        );

        $result = $handler->handle(new Conversacion(['tipo_flujo' => 'inasistencia']), ['text' => '31/02/2026']);

        $this->assertFalse($result->isValid);
        $this->assertSame('invalid_date', $result->errorCode);
    }

    public function test_restart_returns_to_main_menu_and_clears_flow_context(): void
    {
        $handler = new AvisoFechaDesdeStepHandler(
            new DateInputValidator(),
            new ConversationContextService(),
        );

        $conversation = new Conversacion([
            'tipo_flujo' => 'inasistencia',
            'metadata' => [
                'identificacion' => ['legajo' => '10001'],
                'aviso' => ['fecha_desde' => '2026-03-20'],
            ],
        ]);

        $result = $handler->handle($conversation, ['text' => 'menu']);

        $this->assertTrue($result->isValid);
        $this->assertSame('menu_principal', $result->nextStep);
        $this->assertSame([], $result->payload['conversation_updates']['metadata']['aviso']);
        $this->assertSame([], $result->payload['conversation_updates']['metadata']['identificacion']);
    }
}
