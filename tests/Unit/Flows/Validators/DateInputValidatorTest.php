<?php

namespace Tests\Unit\Flows\Validators;

use App\Flows\Validators\DateInputValidator;
use App\Models\Conversacion;
use Tests\TestCase;

class DateInputValidatorTest extends TestCase
{
    public function test_returns_required_when_input_is_empty(): void
    {
        $result = (new DateInputValidator())->validate(new Conversacion(), ['text' => '']);

        $this->assertFalse($result->isValid);
        $this->assertSame('required', $result->errorCode);
    }

    public function test_returns_invalid_date_when_format_is_incorrect(): void
    {
        config()->set('medicina_laboral.avisos.input_date_format', 'd/m/Y');

        $result = (new DateInputValidator())->validate(new Conversacion(), ['text' => '2026-03-19']);

        $this->assertFalse($result->isValid);
        $this->assertSame('invalid_date', $result->errorCode);
    }

    public function test_returns_normalized_date_when_valid(): void
    {
        config()->set('medicina_laboral.avisos.input_date_format', 'd/m/Y');

        $result = (new DateInputValidator())->validate(new Conversacion(), ['text' => '19/03/2026']);

        $this->assertTrue($result->isValid);
        $this->assertSame('2026-03-19', $result->normalized['date']);
    }
}
