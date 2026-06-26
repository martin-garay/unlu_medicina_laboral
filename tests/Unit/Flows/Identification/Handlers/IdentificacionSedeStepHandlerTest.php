<?php

namespace Tests\Unit\Flows\Identification\Handlers;

use App\Flows\Identification\Handlers\IdentificacionSedeStepHandler;
use App\Flows\Validators\SedeValidator;
use App\Models\Conversacion;
use App\Services\Conversation\ConversationContextService;
use Tests\TestCase;

class IdentificacionSedeStepHandlerTest extends TestCase
{
    public function test_valid_sede_moves_to_jornada_and_stores_label(): void
    {
        $handler = new IdentificacionSedeStepHandler(
            new SedeValidator(),
            new ConversationContextService(),
        );

        $result = $handler->handle(new Conversacion(['tipo_flujo' => 'certificado']), ['text' => '1']);

        $this->assertTrue($result->isValid);
        $this->assertSame('identificacion_jornada', $result->nextStep);
        $this->assertSame(__('whatsapp.identificacion.jornada_laboral'), $result->menuConfig['body_text']);
        $this->assertSame('jornada_planta_permanente', $result->menuConfig['buttons'][0]['id']);
        $this->assertSame(
            'Sede Central',
            $result->payload['conversation_updates']['metadata']['identificacion']['sede']
        );
        $this->assertSame(
            'central',
            $result->payload['conversation_updates']['metadata']['identificacion']['sede_key']
        );
    }

    public function test_invalid_sede_returns_catalog_menu(): void
    {
        $handler = new IdentificacionSedeStepHandler(
            new SedeValidator(),
            new ConversationContextService(),
        );

        $result = $handler->handle(new Conversacion(['tipo_flujo' => 'certificado']), ['text' => '9']);

        $this->assertFalse($result->isValid);
        $this->assertSame('sede_invalida', $result->errorCode);
        $this->assertSame(__('whatsapp.identificacion.sede'), $result->menuConfig['body_text']);
        $this->assertSame('sede_central', $result->menuConfig['buttons'][0]['id']);
        $this->assertSame('sede_campus', $result->menuConfig['buttons'][1]['id']);
    }
}
