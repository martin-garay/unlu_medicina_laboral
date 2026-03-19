<?php

namespace Tests\Unit\Flows\Identification\Handlers;

use App\Flows\Identification\Handlers\IdentificacionLegajoStepHandler;
use App\Flows\Validators\LegajoValidator;
use App\Models\Conversacion;
use App\Services\Conversation\ConversationContextService;
use App\Services\Mapuche\Contracts\MapucheWorkerProvider;
use App\Services\Mapuche\MapucheWorkerRecord;
use Tests\TestCase;

class IdentificacionLegajoStepHandlerTest extends TestCase
{
    public function test_returns_error_when_mapuche_provider_cannot_resolve_legajo(): void
    {
        $handler = new IdentificacionLegajoStepHandler(
            new LegajoValidator(),
            new ConversationContextService(),
            new class implements MapucheWorkerProvider
            {
                public function findByLegajo(string $legajo): ?MapucheWorkerRecord
                {
                    return null;
                }
            }
        );

        $result = $handler->handle(new Conversacion(), ['text' => '12345']);

        $this->assertFalse($result->isValid);
        $this->assertSame('legajo_no_encontrado', $result->errorCode);
        $this->assertSame('whatsapp.errores.legajo_no_encontrado', $result->messageKey);
    }

    public function test_stores_mapuche_lookup_snapshot_when_legajo_is_resolved(): void
    {
        $handler = new IdentificacionLegajoStepHandler(
            new LegajoValidator(),
            new ConversationContextService(),
            new class implements MapucheWorkerProvider
            {
                public function findByLegajo(string $legajo): ?MapucheWorkerRecord
                {
                    return new MapucheWorkerRecord($legajo, 'Laura Diaz', 'Sede Central', 'Manana', 'mock_test');
                }
            }
        );

        $result = $handler->handle(new Conversacion(), ['text' => '12345']);

        $this->assertTrue($result->isValid);
        $this->assertSame('identificacion_sede', $result->nextStep);
        $this->assertSame('12345', $result->payload['conversation_updates']['metadata']['identificacion']['legajo']);
        $this->assertSame(
            'Laura Diaz',
            $result->payload['conversation_updates']['metadata']['identificacion']['mapuche_lookup']['nombre_completo']
        );
        $this->assertSame(
            'mock_test',
            $result->payload['conversation_updates']['metadata']['identificacion']['mapuche_lookup']['source']
        );
    }
}
