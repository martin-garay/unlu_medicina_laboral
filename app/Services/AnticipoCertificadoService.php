<?php

namespace App\Services;

use App\Flows\Common\StepResult;
use App\Models\AnticipoCertificado;
use App\Models\Conversacion;
use App\Services\Storage\Contracts\FinalAttachmentStorage;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AnticipoCertificadoService
{
    public function __construct(
        private readonly CertificadoMessageService $certificadoMessageService,
        private readonly FinalAttachmentStorage $finalAttachmentStorage,
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
                $storedAttachment = $this->finalAttachmentStorage->persist($attachment, $conversation, $anticipo);

                $anticipo->archivos()->create([
                    'uuid' => (string) Str::uuid(),
                    'conversacion_id' => $conversation->id,
                    ...$storedAttachment->toArray(),
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
}
