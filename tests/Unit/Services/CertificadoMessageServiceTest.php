<?php

namespace Tests\Unit\Services;

use App\Models\Conversacion;
use App\Services\CertificadoMessageService;
use Tests\TestCase;

class CertificadoMessageServiceTest extends TestCase
{
    public function test_build_draft_summary_template_data_maps_identification_and_attachment_references(): void
    {
        $conversation = new Conversacion([
            'metadata' => [
                'identificacion' => [
                    'nombre_completo' => 'Ana Perez',
                    'legajo' => '123',
                    'sede' => 'Sede Central',
                    'jornada_laboral' => 'Manana',
                ],
                'certificado' => [
                    'numero_aviso' => 'AV-10',
                    'tipo_certificado' => 'manuscrito',
                    'tipo_certificado_label' => 'Manuscrito',
                    'adjuntos' => [
                        [
                            'filename' => 'certificado.pdf',
                            'provider_media_id' => 'media-1',
                        ],
                        [
                            'caption' => 'foto 2',
                            'provider_media_id' => 'media-2',
                        ],
                    ],
                ],
            ],
        ]);

        $data = app(CertificadoMessageService::class)->buildDraftSummaryTemplateData($conversation, [
            'mensaje_estado' => 'Pendiente de siguiente etapa',
        ]);

        $this->assertSame('Ana Perez', $data['nombre']);
        $this->assertSame('Sede Central', $data['sede']);
        $this->assertSame('AV-10', $data['numero_aviso']);
        $this->assertSame('Manuscrito', $data['tipo_certificado']);
        $this->assertSame(2, $data['cantidad_archivos']);
        $this->assertSame(['certificado.pdf', 'foto 2'], $data['nombres_o_referencias_archivos']);
        $this->assertSame('Pendiente de siguiente etapa', $data['mensaje_estado']);
    }
}
