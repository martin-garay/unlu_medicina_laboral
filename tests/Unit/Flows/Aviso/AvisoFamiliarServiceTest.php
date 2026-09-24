<?php

namespace Tests\Unit\Flows\Aviso;

use App\Services\AvisoFamiliarService;
use Tests\TestCase;

class AvisoFamiliarServiceTest extends TestCase
{
    public function test_name_is_required_and_length_uses_configuration(): void
    {
        $service = new AvisoFamiliarService();
        config()->set('medicina_laboral.avisos.nombre_familiar_max_length', 10);
        foreach ([null, '', '   ', ['Ana'], str_repeat('a', 11)] as $value) {
            $this->assertFalse($service->validName($value));
        }
        $this->assertTrue($service->validName(' Ana Pérez '));
    }

    public function test_catalog_accepts_numeric_text_and_interactive_selections(): void
    {
        $service = new AvisoFamiliarService();
        $index = 0;
        foreach ($service->catalog() as $key => $label) {
            $index++;
            foreach ([['text' => (string) $index], ['text' => mb_strtoupper($label)], ['button_id' => 'parentesco_' . $key]] as $input) {
                $this->assertSame($key, $service->resolveParentesco($input));
            }
        }
        foreach ([[], ['text' => '6'], ['text' => 'madre', 'button_id' => 'unknown'], ['text' => 'madre', 'incoming_message_type' => 'image']] as $input) {
            $this->assertNull($service->resolveParentesco($input));
        }
    }

    public function test_menu_and_validation_share_configured_catalog(): void
    {
        config()->set('medicina_laboral.catalogos.parentescos', ['otro' => 'whatsapp.aviso.parentescos.otro']);
        $service = new AvisoFamiliarService();
        $this->assertCount(1, $service->menu()['buttons']);
        $this->assertSame('otro', $service->resolveParentesco(['text' => '1']));
        $this->assertNull($service->resolveParentesco(['text' => 'madre']));
    }
}
