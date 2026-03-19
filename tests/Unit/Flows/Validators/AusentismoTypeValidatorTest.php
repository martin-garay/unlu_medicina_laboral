<?php

namespace Tests\Unit\Flows\Validators;

use App\Flows\Validators\AusentismoTypeValidator;
use App\Models\Conversacion;
use Tests\TestCase;

class AusentismoTypeValidatorTest extends TestCase
{
    public function test_accepts_numeric_option(): void
    {
        $result = (new AusentismoTypeValidator())->validate(new Conversacion(), ['text' => '1']);

        $this->assertTrue($result->isValid);
        $this->assertSame('por_enfermedad', $result->normalized['tipo_ausentismo']);
    }

    public function test_accepts_label_case_insensitively(): void
    {
        $result = (new AusentismoTypeValidator())->validate(new Conversacion(), ['text' => 'por atención de familiar enfermo']);

        $this->assertTrue($result->isValid);
        $this->assertSame('atencion_familiar_enfermo', $result->normalized['tipo_ausentismo']);
    }

    public function test_rejects_unknown_option(): void
    {
        $result = (new AusentismoTypeValidator())->validate(new Conversacion(), ['text' => 'otra']);

        $this->assertFalse($result->isValid);
        $this->assertSame('invalid_option', $result->errorCode);
    }
}
