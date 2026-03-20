<?php

namespace App\Services;

use App\Flows\Common\StepResult;
use App\Models\AnticipoCertificado;
use App\Models\Conversacion;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AnticipoCertificadoService
{
    public function __construct(
        private readonly CertificadoMessageService $certificadoMessageService,
    ) {
    }

    public function buildConfirmationStepResult(Conversacion $conversation): StepResult
    {
        return StepResult::make(null, [
            'template' => config('medicina_laboral.mensajes.templates.certificado_confirmacion_final'),
            'template_data' => $this->certificadoMessageService->buildConfirmationTemplateData($conversation),
        ]);
    }

    public function createFromConversation(Conversacion $conversation): AnticipoCertificado
    {
        $identificacion = Arr::get($conversation->metadata ?? [], 'identificacion', []);
        $certificado = Arr::get($conversation->metadata ?? [], 'certificado', []);

        return DB::transaction(function () use ($conversation, $identificacion, $certificado) {
            $anticipo = AnticipoCertificado::create([
                'uuid' => (string) Str::uuid(),
                'numero_anticipo' => null,
                'conversacion_id' => $conversation->id,
                'aviso_id' => $certificado['aviso_id'] ?? null,
                'wa_number' => $conversation->wa_number,
                'nombre_completo' => $identificacion['nombre_completo'] ?? null,
                'legajo' => $identificacion['legajo'] ?? null,
                'sede' => $identificacion['sede'] ?? null,
                'jornada_laboral' => $identificacion['jornada_laboral'] ?? null,
                'tipo_certificado' => $certificado['tipo_certificado'] ?? null,
                'estado' => 'registrado',
                'observaciones' => $certificado['observaciones'] ?? null,
                'registrado_en' => now(),
                'metadata' => [
                    'identificacion' => $identificacion,
                    'certificado' => $certificado,
                ],
            ]);

            $anticipo->forceFill([
                'numero_anticipo' => $this->displayNumber($anticipo),
            ])->save();

            foreach ($certificado['adjuntos'] ?? [] as $attachment) {
                $anticipo->archivos()->create([
                    'uuid' => (string) Str::uuid(),
                    'conversacion_id' => $conversation->id,
                    'provider_file_id' => $attachment['provider_media_id'] ?? null,
                    'nombre_original' => $attachment['filename'] ?? null,
                    'mime_type' => $attachment['mime_type'] ?? null,
                    'extension' => $this->resolveExtension($attachment['filename'] ?? null),
                    'size_bytes' => $attachment['size_bytes'] ?? null,
                    'storage_disk' => $attachment['storage_disk'] ?? config('medicina_laboral.storage.draft_attachments.disk'),
                    'storage_path' => $attachment['storage_path'] ?? null,
                    'hash_archivo' => $attachment['sha256'] ?? null,
                    'estado_validacion' => 'aceptado',
                    'motivo_rechazo' => null,
                    'metadata' => [
                        'caption' => $attachment['caption'] ?? null,
                        'source_type' => $attachment['source_type'] ?? null,
                        'storage_driver' => $attachment['storage_driver'] ?? null,
                        'storage_status' => $attachment['storage_status'] ?? null,
                        'stored_at' => $attachment['stored_at'] ?? null,
                    ],
                ]);
            }

            $conversation->forceFill([
                'anticipo_certificado_id' => $anticipo->id,
            ])->save();

            return $anticipo->refresh();
        });
    }

    public function buildRegisteredStepResult(AnticipoCertificado $anticipo): StepResult
    {
        return StepResult::make(null, [
            'template' => config('medicina_laboral.mensajes.templates.certificado_registrado'),
            'template_data' => $this->certificadoMessageService->buildRegisteredTemplateData($anticipo),
        ]);
    }

    public function displayNumber(AnticipoCertificado $anticipo): string
    {
        return $anticipo->numero_anticipo ?: 'AC-' . $anticipo->id;
    }

    private function resolveExtension(?string $filename): ?string
    {
        if ($filename === null || $filename === '') {
            return null;
        }

        $extension = pathinfo($filename, PATHINFO_EXTENSION);

        return $extension !== '' ? mb_strtolower($extension) : null;
    }
}
