<?php

namespace Tests\Unit\Services;

use App\Flows\Validators\AusentismoTypeValidator;
use App\Flows\Validators\SedeValidator;
use App\Models\Conversacion;
use App\Models\Parentesco;
use App\Models\Sede;
use App\Models\TipoAusentismo;
use App\Services\AvisoFamiliarService;
use App\Services\Catalogos\ChatCatalogService;
use Tests\Concerns\CreatesChatCatalogSchema;
use Tests\TestCase;

class ChatCatalogServiceTest extends TestCase
{
    use CreatesChatCatalogSchema;

    protected function setUp(): void
    {
        parent::setUp();
        $this->createChatCatalogSchema();
    }

    public function test_menu_and_validation_use_the_active_database_catalog_in_order(): void
    {
        Sede::query()->create(['codigo' => 'nueva_sede', 'nombre' => 'Nueva sede', 'orden' => 5]);

        $service = app(ChatCatalogService::class);
        $this->assertSame('sede_nueva_sede', $service->menu('sedes')['buttons'][0]['id']);
        $this->assertTrue((new SedeValidator)->validate(new Conversacion, ['button_id' => 'sede_nueva_sede'])->isValid);
        $this->assertSame('nueva_sede', (new SedeValidator)->validate(new Conversacion, ['text' => '1'])->normalized['sede_key']);
    }

    public function test_inactive_options_are_not_offered_or_accepted(): void
    {
        Sede::query()->where('codigo', 'central')->update(['activo' => false]);
        $service = app(ChatCatalogService::class);

        $this->assertNotContains('sede_central', array_column($service->menu('sedes')['buttons'], 'id'));
        $this->assertFalse((new SedeValidator)->validate(new Conversacion, ['button_id' => 'sede_central'])->isValid);
    }

    public function test_open_menu_snapshot_survives_later_label_and_active_changes(): void
    {
        $service = app(ChatCatalogService::class);
        $menu = $service->menu('sedes');
        $conversation = new Conversacion(['metadata' => [
            'catalog_menu_snapshot' => ['sedes' => $menu['catalog_snapshot']],
        ]]);
        Sede::query()->where('codigo', 'central')->update(['nombre' => 'Renombrada', 'activo' => false]);

        $result = (new SedeValidator)->validate($conversation, ['button_id' => 'sede_central']);

        $this->assertTrue($result->isValid);
        $this->assertSame('Sede Central', $result->normalized['sede_label']);
    }

    public function test_database_capability_controls_family_subflow(): void
    {
        TipoAusentismo::query()->where('codigo', 'por_enfermedad')->update(['requiere_datos_familiar' => true]);
        $result = (new AusentismoTypeValidator)->validate(new Conversacion, ['text' => '1']);

        $this->assertTrue($result->isValid);
        $this->assertTrue($result->normalized['requiere_datos_familiar']);
    }

    public function test_catalogs_over_ten_options_fall_back_to_numbered_text(): void
    {
        foreach (range(1, 8) as $number) {
            Parentesco::query()->create([
                'codigo' => 'parentesco_'.$number,
                'nombre' => 'Parentesco '.$number,
                'orden' => $number + 5,
            ]);
        }

        $service = app(ChatCatalogService::class);
        $menu = $service->menu('parentescos');

        $this->assertSame('text', $menu['type']);
        $this->assertSame([], $menu['buttons']);
        $this->assertStringContainsString('9. Parentesco 8', $menu['body_text']);
        $this->assertSame('parentesco_8', app(AvisoFamiliarService::class)->resolveParentesco(
            ['text' => '9'],
            new Conversacion(['metadata' => ['catalog_menu_snapshot' => ['parentescos' => $menu['catalog_snapshot']]]]),
        ));
    }

    public function test_more_than_three_sedes_use_list_with_sede_opening_text_and_original_ids(): void
    {
        Sede::query()->delete();
        foreach (range(1, 4) as $number) {
            Sede::query()->create(['codigo' => 'sede_'.$number, 'nombre' => 'Sede '.$number, 'orden' => $number]);
        }

        $menu = app(ChatCatalogService::class)->menu('sedes');

        $this->assertSame('list', $menu['type']);
        $this->assertSame('Ver sedes', $menu['button_text']);
        $this->assertSame([
            ['id' => 'sede_sede_1', 'title' => 'Sede 1', 'description' => ''],
            ['id' => 'sede_sede_2', 'title' => 'Sede 2', 'description' => ''],
            ['id' => 'sede_sede_3', 'title' => 'Sede 3', 'description' => ''],
            ['id' => 'sede_sede_4', 'title' => 'Sede 4', 'description' => ''],
        ], $menu['buttons']);
    }

    public function test_each_catalog_uses_its_own_short_opening_text(): void
    {
        $service = app(ChatCatalogService::class);
        foreach ([
            'sedes' => 'Ver sedes',
            'tipos_ausentismo' => 'Ver ausentismos',
            'tipos_certificado' => 'Ver certificados',
            'parentescos' => 'Ver parentescos',
        ] as $catalog => $expected) {
            $this->assertSame($expected, $service->menu($catalog)['button_text']);
            $this->assertLessThanOrEqual(20, mb_strlen($service->menu($catalog)['button_text']));
        }
        $this->assertSame('list', $service->menu('parentescos')['type']);
    }
}
