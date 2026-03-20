<?php

namespace Tests\Unit\Flows\Certificado\Handlers;

use App\Flows\Certificado\Handlers\CertificadoNumeroAvisoStepHandler;
use App\Flows\Validators\AvisoReferenciaValidator;
use App\Models\Aviso;
use App\Models\Conversacion;
use App\Services\Conversation\ConversationContextService;
use Tests\Concerns\CreatesTestingSchema;
use Tests\TestCase;

class CertificadoNumeroAvisoStepHandlerTest extends TestCase
{
    use CreatesTestingSchema;

    protected function setUp(): void
    {
        parent::setUp();
        $this->createTestingSchema();
    }

    public function test_valid_aviso_moves_flow_to_certificado_tipo(): void
    {
        $aviso = Aviso::create([
            'tipo' => 'inasistencia',
            'legajo' => '123',
            'created_at' => now()->subHours(1),
            'updated_at' => now()->subHours(1),
        ]);

        $handler = new CertificadoNumeroAvisoStepHandler(
            new AvisoReferenciaValidator(),
            new ConversationContextService(),
        );

        $conversation = new Conversacion([
            'tipo_flujo' => 'certificado',
            'metadata' => [
                'identificacion' => [
                    'legajo' => '123',
                ],
            ],
        ]);

        $result = $handler->handle($conversation, ['text' => 'AV-' . $aviso->id]);

        $this->assertTrue($result->isValid);
        $this->assertSame('certificado_tipo', $result->nextStep);
        $this->assertStringContainsString('1. Manuscrito', $result->message);
        $this->assertStringContainsString('2. Electrónico', $result->message);
        $this->assertSame($aviso->id, $result->payload['conversation_updates']['metadata']['certificado']['aviso_id']);
    }

    public function test_invalid_aviso_returns_legajo_error(): void
    {
        Aviso::create([
            'tipo' => 'inasistencia',
            'legajo' => '999',
            'created_at' => now()->subHours(1),
            'updated_at' => now()->subHours(1),
        ]);

        $handler = new CertificadoNumeroAvisoStepHandler(
            new AvisoReferenciaValidator(),
            new ConversationContextService(),
        );

        $conversation = new Conversacion([
            'tipo_flujo' => 'certificado',
            'metadata' => [
                'identificacion' => [
                    'legajo' => '123',
                ],
            ],
        ]);

        $result = $handler->handle($conversation, ['text' => '1']);

        $this->assertFalse($result->isValid);
        $this->assertSame('aviso_no_corresponde_legajo', $result->errorCode);
    }
}
