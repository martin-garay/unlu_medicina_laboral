<?php

namespace Tests\Unit\Services\Conversation;

use App\Flows\Common\Contracts\StepHandler;
use App\Flows\Common\StepResult;
use App\Models\Conversacion;
use App\Services\Conversation\ConversationFlowResolver;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class ConversationFlowResolverTest extends TestCase
{
    public function test_returns_first_handler_that_supports_the_current_step(): void
    {
        $conversation = new Conversacion([
            'paso_actual' => 'menu_principal',
        ]);

        $matchingHandler = new class implements StepHandler {
            public function stepKey(): string
            {
                return 'menu_principal';
            }

            public function supports(Conversacion $conversation): bool
            {
                return $conversation->currentStepKey() === $this->stepKey();
            }

            public function handle(Conversacion $conversation, array $input = []): StepResult
            {
                return StepResult::make();
            }
        };

        $resolver = new ConversationFlowResolver([
            new class implements StepHandler {
                public function stepKey(): string { return 'otro'; }
                public function supports(Conversacion $conversation): bool { return false; }
                public function handle(Conversacion $conversation, array $input = []): StepResult { return StepResult::make(); }
            },
            $matchingHandler,
        ]);

        $this->assertSame($matchingHandler, $resolver->resolve($conversation));
    }

    public function test_throws_runtime_exception_when_no_handler_supports_the_step(): void
    {
        $conversation = new Conversacion([
            'paso_actual' => 'inexistente',
        ]);

        $resolver = new ConversationFlowResolver([]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('inexistente');

        $resolver->resolve($conversation);
    }
}
