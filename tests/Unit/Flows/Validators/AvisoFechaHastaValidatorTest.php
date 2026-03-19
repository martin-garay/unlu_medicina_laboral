<?php

namespace Tests\Unit\Flows\Validators;

use App\Flows\Validators\AvisoFechaHastaValidator;
use App\Models\Conversacion;
use Tests\TestCase;

class AvisoFechaHastaValidatorTest extends TestCase
{
    public function test_returns_before_start_date_when_end_date_is_before_start_date(): void
    {
        config()->set('medicina_laboral.avisos.input_date_format', 'd/m/Y');

        $conversation = new Conversacion([
            'metadata' => [
                'aviso' => [
                    'fecha_desde' => '2026-03-20',
                ],
            ],
        ]);

        $result = (new AvisoFechaHastaValidator())->validate($conversation, ['text' => '19/03/2026']);

        $this->assertFalse($result->isValid);
        $this->assertSame('before_start_date', $result->errorCode);
    }

    public function test_returns_normalized_date_when_end_date_is_valid(): void
    {
        config()->set('medicina_laboral.avisos.input_date_format', 'd/m/Y');

        $conversation = new Conversacion([
            'metadata' => [
                'aviso' => [
                    'fecha_desde' => '2026-03-19',
                ],
            ],
        ]);

        $result = (new AvisoFechaHastaValidator())->validate($conversation, ['text' => '20/03/2026']);

        $this->assertTrue($result->isValid);
        $this->assertSame('2026-03-20', $result->normalized['date']);
    }
}
