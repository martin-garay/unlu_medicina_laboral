<?php

namespace Tests\Unit\Flows\Validators;

use App\Flows\Validators\TipoCertificadoValidator;
use App\Models\Conversacion;
use Tests\TestCase;

class TipoCertificadoValidatorTest extends TestCase
{
    public function test_accepts_numeric_option(): void
    {
        $result = (new TipoCertificadoValidator())->validate(new Conversacion(), ['text' => '2']);

        $this->assertTrue($result->isValid);
        $this->assertSame('electronico', $result->normalized['tipo_certificado']);
    }

    public function test_accepts_label_case_insensitively(): void
    {
        $result = (new TipoCertificadoValidator())->validate(new Conversacion(), ['text' => 'manuscrito']);

        $this->assertTrue($result->isValid);
        $this->assertSame('manuscrito', $result->normalized['tipo_certificado']);
    }

    public function test_rejects_unknown_option(): void
    {
        $result = (new TipoCertificadoValidator())->validate(new Conversacion(), ['text' => 'otro']);

        $this->assertFalse($result->isValid);
        $this->assertSame('invalid_option', $result->errorCode);
    }
}
