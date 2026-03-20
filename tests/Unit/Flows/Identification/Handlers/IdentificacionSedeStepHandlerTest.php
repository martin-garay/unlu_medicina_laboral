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
        $this->assertSame(
            'Sede Central',
            $result->payload['conversation_updates']['metadata']['identificacion']['sede']
        );
        $this->assertSame(
            'central',
            $result->payload['conversation_updates']['metadata']['identificacion']['sede_key']
        );
    }

    public function test_invalid_sede_returns_numbered_options_message(): void
    {
        $handler = new IdentificacionSedeStepHandler(
            new SedeValidator(),
            new ConversationContextService(),
        );

        $result = $handler->handle(new Conversacion(['tipo_flujo' => 'certificado']), ['text' => '9']);

        $this->assertFalse($result->isValid);
        $this->assertSame('sede_invalida', $result->errorCode);
        $this->assertStringContainsString('1. Sede Central', $result->message);
        $this->assertStringContainsString('2. Campus Luján', $result->message);
    }
}
