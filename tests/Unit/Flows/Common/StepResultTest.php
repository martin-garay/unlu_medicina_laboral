<?php

namespace Tests\Unit\Flows\Common;

use App\Flows\Common\StepResult;
use PHPUnit\Framework\TestCase;

class StepResultTest extends TestCase
{
    public function test_make_builds_a_valid_result_with_expected_attributes(): void
    {
        $result = StepResult::make('whatsapp.menu.prompt', [
            'next_step' => 'menu_principal',
            'next_state' => 'menu_principal',
            'should_show_menu' => true,
            'payload' => ['foo' => 'bar'],
        ]);

        $this->assertTrue($result->isValid);
        $this->assertSame('whatsapp.menu.prompt', $result->messageKey);
        $this->assertSame('menu_principal', $result->nextStep);
        $this->assertSame('menu_principal', $result->nextState);
        $this->assertTrue($result->shouldShowMenu);
        $this->assertSame(['foo' => 'bar'], $result->payload);
    }

    public function test_invalid_marks_result_as_invalid_and_sets_error_code(): void
    {
        $result = StepResult::invalid('invalid_option', 'whatsapp.errores.invalid_option', [
            'increment_attempts' => 1,
        ]);

        $this->assertFalse($result->isValid);
        $this->assertSame('invalid_option', $result->errorCode);
        $this->assertSame('whatsapp.errores.invalid_option', $result->messageKey);
        $this->assertSame(1, $result->incrementAttempts);
    }

    public function test_has_message_is_true_for_message_key_message_or_template(): void
    {
        $this->assertTrue(StepResult::make('whatsapp.menu.prompt')->hasMessage());
        $this->assertTrue(StepResult::make(null, ['message' => 'hola'])->hasMessage());
        $this->assertTrue(StepResult::make(null, ['template' => 'messages.aviso.confirmacion_final'])->hasMessage());
        $this->assertFalse(StepResult::make()->hasMessage());
    }

    public function test_helper_methods_reflect_transition_and_closing_flags(): void
    {
        $transition = StepResult::make(null, ['next_state' => 'identificacion_nombre']);
        $cancel = StepResult::make(null, ['should_cancel' => true]);
        $finish = StepResult::make(null, ['should_finish' => true]);

        $this->assertTrue($transition->hasNextState());
        $this->assertFalse($transition->closesConversation());
        $this->assertTrue($cancel->closesConversation());
        $this->assertTrue($finish->closesConversation());
    }
}
