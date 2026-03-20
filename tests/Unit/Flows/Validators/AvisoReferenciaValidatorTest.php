<?php

namespace Tests\Unit\Flows\Validators;

use App\Flows\Validators\AvisoReferenciaValidator;
use App\Models\Aviso;
use App\Models\Conversacion;
use Tests\Concerns\CreatesTestingSchema;
use Tests\TestCase;

class AvisoReferenciaValidatorTest extends TestCase
{
    use CreatesTestingSchema;

    protected function setUp(): void
    {
        parent::setUp();
        $this->createTestingSchema();
    }

    public function test_returns_valid_result_for_matching_aviso_reference(): void
    {
        $aviso = Aviso::create([
            'tipo' => 'inasistencia',
            'legajo' => '123',
            'created_at' => now()->subHours(2),
            'updated_at' => now()->subHours(2),
        ]);

        $conversation = new Conversacion([
            'metadata' => [
                'identificacion' => [
                    'legajo' => '123',
                ],
            ],
        ]);

        $result = (new AvisoReferenciaValidator())->validate($conversation, ['text' => 'AV-' . $aviso->id]);

        $this->assertTrue($result->isValid);
        $this->assertSame($aviso->id, $result->normalized['aviso_id']);
        $this->assertSame('AV-' . $aviso->id, $result->normalized['numero_aviso']);
    }

    public function test_returns_invalid_when_aviso_does_not_match_identification_legajo(): void
    {
        Aviso::create([
            'tipo' => 'inasistencia',
            'legajo' => '999',
            'created_at' => now()->subHours(2),
            'updated_at' => now()->subHours(2),
        ]);

        $conversation = new Conversacion([
            'metadata' => [
                'identificacion' => [
                    'legajo' => '123',
                ],
            ],
        ]);

        $result = (new AvisoReferenciaValidator())->validate($conversation, ['text' => '1']);

        $this->assertFalse($result->isValid);
        $this->assertSame('aviso_no_corresponde_legajo', $result->errorCode);
    }
}
