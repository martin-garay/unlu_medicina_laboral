<?php

namespace Tests\Unit\Flows\Validators;

use App\Flows\Validators\JornadaLaboralValidator;
use App\Models\Conversacion;
use Tests\TestCase;

class JornadaLaboralValidatorTest extends TestCase
{
    public function test_accepts_numeric_option(): void
    {
        $result = (new JornadaLaboralValidator())->validate(new Conversacion(), ['text' => '1']);

        $this->assertTrue($result->isValid);
        $this->assertSame('planta_permanente', $result->normalized['jornada_laboral']);
    }

    public function test_accepts_button_id(): void
    {
        $result = (new JornadaLaboralValidator())->validate(new Conversacion(), [
            'button_id' => 'jornada_parcial',
        ]);

        $this->assertTrue($result->isValid);
        $this->assertSame('parcial', $result->normalized['jornada_laboral']);
        $this->assertSame('Parcial', $result->normalized['jornada_laboral_label']);
    }

    public function test_rejects_unknown_option(): void
    {
        $result = (new JornadaLaboralValidator())->validate(new Conversacion(), ['text' => 'otra']);

        $this->assertFalse($result->isValid);
        $this->assertSame('invalid_option', $result->errorCode);
    }
}
