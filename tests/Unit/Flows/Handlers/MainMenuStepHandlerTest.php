<?php

namespace Tests\Unit\Flows\Handlers;

use App\Flows\Common\Contracts\Validator;
use App\Flows\Common\ValidationResult;
use App\Flows\Handlers\MainMenuStepHandler;
use App\Models\Conversacion;
use App\Services\Conversation\ConversationContextService;
use Mockery;
use Tests\TestCase;

class MainMenuStepHandlerTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_first_interaction_presents_menu_when_selection_is_not_yet_valid(): void
    {
        $validator = Mockery::mock(Validator::class);
        $validator->shouldReceive('validate')
            ->once()
            ->andReturn(ValidationResult::invalid('invalid_option'));

        $handler = new MainMenuStepHandler($validator, new ConversationContextService());
        $conversation = new Conversacion([
            'cantidad_mensajes_recibidos' => 0,
            'paso_actual' => 'menu_principal',
        ]);

        $result = $handler->handle($conversation, ['text' => 'hola']);

        $this->assertTrue($result->shouldShowMenu);
        $this->assertSame(config('medicina_laboral.mensajes.templates.bienvenida'), $result->template);
        $this->assertSame('main_menu_presented', $result->payload['event_name']);
    }

    public function test_selecting_inasistencia_routes_to_identificacion_and_sets_flow_data(): void
    {
        $validator = Mockery::mock(Validator::class);
        $validator->shouldReceive('validate')
            ->once()
            ->andReturn(ValidationResult::valid([
                'selected_option' => 'inasistencia',
            ]));

        $handler = new MainMenuStepHandler($validator, new ConversationContextService());
        $conversation = new Conversacion([
            'cantidad_mensajes_recibidos' => 1,
            'paso_actual' => 'menu_principal',
            'metadata' => [],
        ]);

        $result = $handler->handle($conversation, ['text' => '2']);

        $this->assertSame('identificacion_nombre', $result->nextStep);
        $this->assertSame('identificacion_nombre', $result->nextState);
        $this->assertSame('inasistencia', $result->payload['conversation_updates']['tipo']);
        $this->assertSame('inasistencia', $result->payload['conversation_updates']['tipo_flujo']);
    }
}
