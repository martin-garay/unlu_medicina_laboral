<?php

namespace App\Services;

use App\Models\AnticipoCertificado;
use App\Models\Conversacion;
use Illuminate\Support\Arr;

class CertificadoMessageService
{
    public function buildDraftSummaryTemplateData(Conversacion $conversation, array $overrides = []): array
    {
        return $this->buildTemplateData($conversation, $overrides);
    }

    public function buildConfirmationTemplateData(Conversacion $conversation, array $overrides = []): array
    {
        return $this->buildTemplateData($conversation, array_merge($overrides, [
            'mensaje_estado' => __('whatsapp.certificado.confirmacion_final'),
        ]));
    }

    public function buildRegisteredTemplateData(AnticipoCertificado $anticipo): array
    {
        return [
            'numero_certificado' => $anticipo->numero_anticipo,
            'nombre' => $anticipo->nombre_completo ?? '-',
            'legajo' => $anticipo->legajo ?? '-',
            'aviso_asociado' => $anticipo->aviso?->id !== null ? 'AV-' . $anticipo->aviso->id : '-',
            'tipo_certificado' => $anticipo->tipo_certificado ?? '-',
            'fecha_hora_recepcion' => $anticipo->registrado_en?->format('d/m/Y H:i') ?? '-',
        ];
    }

    private function buildTemplateData(Conversacion $conversation, array $overrides = []): array
    {
        $identificacion = Arr::get($conversation->metadata ?? [], 'identificacion', []);
        $certificado = array_merge(
            Arr::get($conversation->metadata ?? [], 'certificado', []),
            $overrides
        );

        $adjuntos = $certificado['adjuntos'] ?? [];
        $referenciasAdjuntos = array_map(function (array $adjunto): string {
            return $adjunto['filename']
                ?? $adjunto['caption']
                ?? $adjunto['provider_media_id']
                ?? ($adjunto['source_type'] ?? 'adjunto');
        }, $adjuntos);

        return [
            'nombre' => $identificacion['nombre_completo'] ?? '-',
            'legajo' => $identificacion['legajo'] ?? '-',
            'sede' => $identificacion['sede'] ?? '-',
            'jornada' => $identificacion['jornada_laboral'] ?? '-',
            'numero_aviso' => $certificado['numero_aviso'] ?? '-',
            'tipo_certificado' => $certificado['tipo_certificado_label'] ?? $certificado['tipo_certificado'] ?? '-',
            'cantidad_archivos' => count($adjuntos),
            'nombres_o_referencias_archivos' => $referenciasAdjuntos,
            'mensaje_estado' => $certificado['mensaje_estado'] ?? null,
        ];
    }
}
