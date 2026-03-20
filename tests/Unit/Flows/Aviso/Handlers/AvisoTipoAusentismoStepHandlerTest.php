<?php

namespace Tests\Unit\Flows\Aviso\Handlers;

use App\Flows\Aviso\Handlers\AvisoTipoAusentismoStepHandler;
use App\Flows\Validators\AusentismoTypeValidator;
use App\Models\Conversacion;
use App\Services\Conversation\ConversationContextService;
use Tests\TestCase;

class AvisoTipoAusentismoStepHandlerTest extends TestCase
{
    public function test_familiar_option_sets_flag_and_moves_to_motivo(): void
    {
        $handler = new AvisoTipoAusentismoStepHandler(
            new AusentismoTypeValidator(),
            new ConversationContextService(),
        );

        $result = $handler->handle(new Conversacion(['tipo_flujo' => 'inasistencia']), ['text' => '2']);

        $this->assertTrue($result->isValid);
        $this->assertSame('aviso_motivo', $result->nextStep);
        $this->assertSame(
            'atencion_familiar_enfermo',
            $result->payload['conversation_updates']['metadata']['aviso']['tipo_ausentismo']
        );
        $this->assertTrue($result->payload['conversation_updates']['metadata']['aviso']['requiere_datos_familiar']);
    }

    public function test_invalid_option_returns_numbered_catalog_message(): void
    {
        $handler = new AvisoTipoAusentismoStepHandler(
            new AusentismoTypeValidator(),
            new ConversationContextService(),
        );

        $result = $handler->handle(new Conversacion(['tipo_flujo' => 'inasistencia']), ['text' => '8']);

        $this->assertFalse($result->isValid);
        $this->assertSame('invalid_option', $result->errorCode);
        $this->assertStringContainsString('1. Por Enfermedad', $result->message);
        $this->assertStringContainsString('2. Por Atención de Familiar Enfermo', $result->message);
    }

    public function test_cancel_returns_to_main_menu(): void
    {
        $handler = new AvisoTipoAusentismoStepHandler(
            new AusentismoTypeValidator(),
            new ConversationContextService(),
        );

        $conversation = new Conversacion([
            'tipo_flujo' => 'inasistencia',
            'metadata' => [
                'aviso' => ['tipo_ausentismo' => 'por_enfermedad'],
            ],
        ]);

        $result = $handler->handle($conversation, ['text' => 'cancelar']);

        $this->assertTrue($result->isValid);
        $this->assertSame('menu_principal', $result->nextStep);
        $this->assertTrue($result->shouldShowMenu);
    }
}
