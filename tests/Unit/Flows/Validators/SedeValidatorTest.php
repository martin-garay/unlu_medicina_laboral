<?php

namespace Tests\Unit\Flows\Validators;

use App\Flows\Validators\SedeValidator;
use App\Models\Conversacion;
use Tests\TestCase;

class SedeValidatorTest extends TestCase
{
    public function test_accepts_numeric_alias_for_configured_sede(): void
    {
        $result = (new SedeValidator())->validate(new Conversacion(), ['text' => '1']);

        $this->assertTrue($result->isValid);
        $this->assertSame('central', $result->normalized['sede_key']);
    }

    public function test_accepts_sede_label_case_insensitively(): void
    {
        $result = (new SedeValidator())->validate(new Conversacion(), ['text' => 'campus luján']);

        $this->assertTrue($result->isValid);
        $this->assertSame('campus', $result->normalized['sede_key']);
    }

    public function test_rejects_unknown_sede(): void
    {
        $result = (new SedeValidator())->validate(new Conversacion(), ['text' => 'desconocida']);

        $this->assertFalse($result->isValid);
        $this->assertSame('sede_invalida', $result->errorCode);
    }
}
