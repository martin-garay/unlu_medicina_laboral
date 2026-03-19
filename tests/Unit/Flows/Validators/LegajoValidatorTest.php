<?php

namespace Tests\Unit\Flows\Validators;

use App\Flows\Validators\LegajoValidator;
use App\Models\Conversacion;
use Tests\TestCase;

class LegajoValidatorTest extends TestCase
{
    public function test_returns_required_when_input_is_empty(): void
    {
        $result = (new LegajoValidator())->validate(new Conversacion(), ['text' => '']);

        $this->assertFalse($result->isValid);
        $this->assertSame('required', $result->errorCode);
    }

    public function test_returns_legajo_invalido_for_non_numeric_input(): void
    {
        $result = (new LegajoValidator())->validate(new Conversacion(), ['text' => '12A34']);

        $this->assertFalse($result->isValid);
        $this->assertSame('legajo_invalido', $result->errorCode);
    }

    public function test_returns_normalized_legajo_when_valid(): void
    {
        $result = (new LegajoValidator())->validate(new Conversacion(), ['text' => '12345']);

        $this->assertTrue($result->isValid);
        $this->assertSame('12345', $result->normalized['legajo']);
    }
}
