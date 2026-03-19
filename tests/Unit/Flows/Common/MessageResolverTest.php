<?php

namespace Tests\Unit\Flows\Common;

use App\Flows\Common\MessageResolver;
use App\Flows\Common\StepResult;
use Illuminate\Contracts\View\Factory as ViewFactory;
use Illuminate\Translation\Translator;
use Mockery;
use Tests\TestCase;

class MessageResolverTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_it_resolves_message_keys_via_translator(): void
    {
        $translator = Mockery::mock(Translator::class);
        $translator->shouldReceive('get')
            ->once()
            ->with('whatsapp.menu.prompt', ['foo' => 'bar'])
            ->andReturn('texto traducido');

        $view = Mockery::mock(ViewFactory::class);
        $resolver = new MessageResolver($translator, $view);

        $result = StepResult::make('whatsapp.menu.prompt', [
            'message_params' => ['foo' => 'bar'],
        ]);

        $this->assertSame('texto traducido', $resolver->resolve($result));
    }

    public function test_it_resolves_templates_via_view_factory(): void
    {
        $translator = Mockery::mock(Translator::class);
        $view = Mockery::mock(ViewFactory::class);
        $viewInstance = Mockery::mock();

        $view->shouldReceive('make')
            ->once()
            ->with('messages.aviso.confirmacion_final', ['nombre' => 'Ana'])
            ->andReturn($viewInstance);

        $viewInstance->shouldReceive('render')
            ->once()
            ->andReturn('template renderizado');

        $resolver = new MessageResolver($translator, $view);

        $result = StepResult::make(null, [
            'template' => 'messages.aviso.confirmacion_final',
            'template_data' => ['nombre' => 'Ana'],
        ]);

        $this->assertSame('template renderizado', $resolver->resolve($result));
    }

    public function test_it_returns_inline_message_before_key_or_template(): void
    {
        $translator = Mockery::mock(Translator::class);
        $view = Mockery::mock(ViewFactory::class);
        $resolver = new MessageResolver($translator, $view);

        $result = StepResult::make('whatsapp.menu.prompt', [
            'message' => 'mensaje directo',
            'template' => 'messages.aviso.confirmacion_final',
        ]);

        $this->assertSame('mensaje directo', $resolver->resolve($result));
    }
}
