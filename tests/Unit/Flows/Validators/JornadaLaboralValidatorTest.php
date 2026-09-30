<?php

namespace Tests\Unit\Flows\Validators;

use App\Flows\Validators\JornadaLaboralValidator;
use App\Models\Conversacion;
use Tests\TestCase;

class JornadaLaboralValidatorTest extends TestCase
{
    public function test_accepts_arbitrary_non_empty_text_as_free_form_jornada(): void
    {
        $result = (new JornadaLaboralValidator)->validate(new Conversacion, ['text' => 'Lunes a viernes de 8 a 14']);

        $this->assertTrue($result->isValid);
        $this->assertSame('Lunes a viernes de 8 a 14', $result->normalized['jornada_laboral_label']);
    }

    public function test_accepts_legacy_button_ids_during_existing_conversations(): void
    {
        $result = (new JornadaLaboralValidator)->validate(new Conversacion, [
            'button_id' => 'jornada_parcial',
        ]);

        $this->assertTrue($result->isValid);
        $this->assertSame('parcial', $result->normalized['jornada_laboral']);
        $this->assertSame('Parcial', $result->normalized['jornada_laboral_label']);
    }

    public function test_rejects_only_empty_text(): void
    {
        $result = (new JornadaLaboralValidator)->validate(new Conversacion, ['text' => '   ']);

        $this->assertFalse($result->isValid);
        $this->assertSame('required', $result->errorCode);
    }

    public function test_rejects_text_that_exceeds_the_storage_limit(): void
    {
        $result = (new JornadaLaboralValidator)->validate(new Conversacion, ['text' => str_repeat('a', 256)]);

        $this->assertFalse($result->isValid);
        $this->assertSame('max_length', $result->errorCode);
    }
}
