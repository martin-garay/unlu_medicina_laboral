<?php

namespace Tests\Unit\Flows\Identification\Handlers;

use App\Flows\Common\Contracts\Validator;
use App\Flows\Common\ValidationResult;
use App\Flows\Identification\Handlers\IdentificacionJornadaStepHandler;
use App\Models\Conversacion;
use App\Services\Conversation\ConversationContextService;
use Tests\TestCase;

class IdentificacionJornadaStepHandlerTest extends TestCase
{
    public function test_routes_inasistencia_flow_to_aviso_fecha_desde(): void
    {
        $handler = new IdentificacionJornadaStepHandler(
            new class implements Validator
            {
                public function validate(Conversacion $conversation, array $input = []): ValidationResult
                {
                    return ValidationResult::valid([
                        'text' => trim((string) ($input['text'] ?? '')),
                    ]);
                }
            },
            new ConversationContextService(),
        );

        $conversation = new Conversacion([
            'tipo_flujo' => 'inasistencia',
            'metadata' => [
                'identificacion' => [
                    'nombre_completo' => 'Ana Perez',
                ],
            ],
        ]);

        $result = $handler->handle($conversation, ['text' => 'Manana']);

        $this->assertTrue($result->isValid);
        $this->assertSame('aviso_fecha_desde', $result->nextStep);
        $this->assertSame(
            'Manana',
            $result->payload['conversation_updates']['metadata']['identificacion']['jornada_laboral']
        );
        $this->assertSame('identificacion_completed', $result->payload['event_name']);
    }

    public function test_routes_certificado_flow_to_certificado_numero_aviso(): void
    {
        $handler = new IdentificacionJornadaStepHandler(
            new class implements Validator
            {
                public function validate(Conversacion $conversation, array $input = []): ValidationResult
                {
                    return ValidationResult::valid([
                        'text' => trim((string) ($input['text'] ?? '')),
                    ]);
                }
            },
            new ConversationContextService(),
        );

        $conversation = new Conversacion([
            'tipo_flujo' => 'certificado',
            'metadata' => [
                'identificacion' => [
                    'legajo' => '10001',
                ],
            ],
        ]);

        $result = $handler->handle($conversation, ['text' => 'Tarde']);

        $this->assertTrue($result->isValid);
        $this->assertSame('certificado_numero_aviso', $result->nextStep);
        $this->assertSame(
            'Tarde',
            $result->payload['conversation_updates']['metadata']['identificacion']['jornada_laboral']
        );
    }

    public function test_cancel_returns_to_main_menu_and_resets_context(): void
    {
        $handler = new IdentificacionJornadaStepHandler(
            new class implements Validator
            {
                public function validate(Conversacion $conversation, array $input = []): ValidationResult
                {
                    return ValidationResult::valid([
                        'text' => trim((string) ($input['text'] ?? '')),
                    ]);
                }
            },
            new ConversationContextService(),
        );

        $conversation = new Conversacion([
            'tipo_flujo' => 'certificado',
            'metadata' => [
                'identificacion' => ['legajo' => '10001'],
                'certificado' => ['aviso_id' => 9],
            ],
            'dni' => '30111222',
        ]);

        $result = $handler->handle($conversation, ['text' => 'cancelar']);

        $this->assertTrue($result->isValid);
        $this->assertSame('menu_principal', $result->nextStep);
        $this->assertTrue($result->shouldShowMenu);
        $this->assertSame([], $result->payload['conversation_updates']['metadata']['identificacion']);
        $this->assertSame([], $result->payload['conversation_updates']['metadata']['certificado']);
        $this->assertNull($result->payload['conversation_updates']['dni']);
        $this->assertNull($result->payload['conversation_updates']['tipo_flujo']);
    }
}
