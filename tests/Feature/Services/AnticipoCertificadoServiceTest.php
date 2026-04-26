<?php

namespace Tests\Feature\Services;

use App\Models\AnticipoCertificado;
use App\Models\Aviso;
use App\Services\AnticipoCertificadoService;
use App\Services\ConversationManager;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\CreatesTestingSchema;
use Tests\TestCase;

class AnticipoCertificadoServiceTest extends TestCase
{
    use CreatesTestingSchema;

    protected function setUp(): void
    {
        parent::setUp();
        $this->createTestingSchema();
    }

    public function test_create_from_conversation_persists_anticipo_and_associated_files(): void
    {
        $conversation = app(ConversationManager::class)->createConversation('5491111111111', [
            'metadata' => [
                'identificacion' => [
                    'nombre_completo' => 'Ana Perez',
                    'legajo' => '123',
                    'sede' => 'Sede Central',
                    'jornada_laboral' => 'Manana',
                ],
                'certificado' => [
                    'aviso_id' => 1,
                    'numero_aviso' => 'AV-1',
                    'tipo_certificado' => 'electronico',
                    'tipo_certificado_label' => 'Electrónico',
                    'adjuntos' => [
                        [
                            'provider_media_id' => 'media-1',
                            'mime_type' => 'application/pdf',
                            'filename' => 'certificado.pdf',
                            'source_type' => 'document',
                            'storage_driver' => 'metadata',
                            'storage_status' => 'metadata_only',
                        ],
                    ],
                ],
            ],
            'tipo_flujo' => 'certificado',
        ]);

        $aviso = Aviso::create([
            'conversacion_id' => $conversation->id,
            'tipo' => 'inasistencia',
            'legajo' => '123',
            'wa_number' => '5491111111111',
        ]);

        $conversation->forceFill([
            'metadata' => array_merge($conversation->metadata ?? [], [
                'certificado' => array_merge($conversation->metadata['certificado'] ?? [], [
                    'aviso_id' => $aviso->id,
                    'numero_aviso' => 'AV-' . $aviso->id,
                ]),
            ]),
        ])->save();

        $anticipo = app(AnticipoCertificadoService::class)->createFromConversation($conversation->refresh());

        $this->assertDatabaseHas('anticipos_certificado', [
            'id' => $anticipo->id,
            'conversacion_id' => $conversation->id,
            'aviso_id' => $aviso->id,
            'tipo_certificado' => 'electronico',
            'estado' => 'inicial',
        ]);
        $this->assertDatabaseHas('anticipo_certificado_archivos', [
            'anticipo_certificado_id' => $anticipo->id,
            'conversacion_id' => $conversation->id,
            'provider_file_id' => 'media-1',
            'nombre_original' => 'certificado.pdf',
            'estado_validacion' => 'aceptado',
        ]);
        $this->assertDatabaseHas('anticipo_certificado_aviso', [
            'anticipo_certificado_id' => $anticipo->id,
            'aviso_id' => $aviso->id,
            'origen' => 'conversacion',
            'estado_vinculo' => 'activo',
        ]);
        $this->assertSame('AC-' . $anticipo->id, $anticipo->numero_anticipo);
        $this->assertSame($anticipo->id, $conversation->refresh()->anticipo_certificado_id);
    }

    public function test_build_registered_step_result_uses_registered_template(): void
    {
        $conversation = app(ConversationManager::class)->createConversation('5491111111111');
        $aviso = Aviso::create([
            'conversacion_id' => $conversation->id,
            'tipo' => 'inasistencia',
            'wa_number' => '5491111111111',
        ]);

        $anticipo = AnticipoCertificado::create([
            'uuid' => 'test-uuid',
            'numero_anticipo' => 'AC-1',
            'conversacion_id' => $conversation->id,
            'aviso_id' => $aviso->id,
            'nombre_completo' => 'Ana Perez',
            'legajo' => '123',
            'tipo_certificado' => 'electronico',
            'estado' => 'inicial',
            'registrado_en' => now(),
        ]);

        $result = app(AnticipoCertificadoService::class)->buildRegisteredStepResult($anticipo->refresh());

        $this->assertSame(config('medicina_laboral.mensajes.templates.certificado_registrado'), $result->template);
        $this->assertSame('AC-1', $result->templateData['numero_certificado']);
        $this->assertSame('AV-' . $aviso->id, $result->templateData['aviso_asociado']);
    }

    public function test_anticipo_and_aviso_can_be_loaded_through_many_to_many_relationships(): void
    {
        $conversation = app(ConversationManager::class)->createConversation('5491111111111');
        $aviso = Aviso::create([
            'conversacion_id' => $conversation->id,
            'tipo' => 'inasistencia',
            'legajo' => '123',
            'wa_number' => '5491111111111',
        ]);
        $otroAviso = Aviso::create([
            'conversacion_id' => $conversation->id,
            'tipo' => 'inasistencia',
            'legajo' => '123',
            'wa_number' => '5491111111111',
        ]);
        $anticipo = AnticipoCertificado::create([
            'uuid' => 'test-uuid',
            'numero_anticipo' => 'AC-1',
            'conversacion_id' => $conversation->id,
            'aviso_id' => $aviso->id,
            'tipo_certificado' => 'electronico',
            'estado' => 'inicial',
            'registrado_en' => now(),
        ]);

        $anticipo->avisos()->attach([
            $aviso->id => [
                'origen' => 'conversacion',
                'estado_vinculo' => 'activo',
                'metadata' => json_encode(['numero_aviso' => 'AV-' . $aviso->id]),
            ],
            $otroAviso->id => [
                'origen' => 'manual',
                'estado_vinculo' => 'activo',
                'metadata' => json_encode(['numero_aviso' => 'AV-' . $otroAviso->id]),
            ],
        ]);

        $this->assertSame(
            [$aviso->id, $otroAviso->id],
            $anticipo->refresh()->avisos()->orderBy('avisos.id')->pluck('avisos.id')->all()
        );
        $this->assertSame(
            [$anticipo->id],
            $aviso->refresh()->anticiposCertificado()->pluck('anticipos_certificado.id')->all()
        );
        $this->assertSame(
            [$anticipo->id],
            $aviso->refresh()->anticiposCertificadoLegacy()->pluck('anticipos_certificado.id')->all()
        );
    }

    public function test_pivot_rejects_duplicate_anticipo_aviso_links(): void
    {
        $conversation = app(ConversationManager::class)->createConversation('5491111111111');
        $aviso = Aviso::create([
            'conversacion_id' => $conversation->id,
            'tipo' => 'inasistencia',
            'wa_number' => '5491111111111',
        ]);
        $anticipo = AnticipoCertificado::create([
            'uuid' => 'test-uuid',
            'numero_anticipo' => 'AC-1',
            'conversacion_id' => $conversation->id,
            'aviso_id' => $aviso->id,
            'tipo_certificado' => 'electronico',
            'estado' => 'inicial',
            'registrado_en' => now(),
        ]);

        DB::table('anticipo_certificado_aviso')->insert([
            'anticipo_certificado_id' => $anticipo->id,
            'aviso_id' => $aviso->id,
            'origen' => 'conversacion',
            'estado_vinculo' => 'activo',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->expectException(QueryException::class);

        DB::table('anticipo_certificado_aviso')->insert([
            'anticipo_certificado_id' => $anticipo->id,
            'aviso_id' => $aviso->id,
            'origen' => 'manual',
            'estado_vinculo' => 'activo',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
