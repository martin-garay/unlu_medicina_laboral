<?php

namespace App\Services;

use App\Flows\Common\StepResult;
use App\Models\Aviso;
use App\Models\Conversacion;
use Carbon\Carbon;
use Illuminate\Support\Arr;

class AvisoService
{
    public function buildConfirmationTemplateData(Conversacion $conversation, array $avisoOverrides = []): array
    {
        $identificacion = Arr::get($conversation->metadata ?? [], 'identificacion', []);
        $aviso = array_merge(Arr::get($conversation->metadata ?? [], 'aviso', []), $avisoOverrides);

        $fechaDesde = $aviso['fecha_desde'] ?? null;
        $fechaHasta = $aviso['fecha_hasta'] ?? null;

        return [
            'nombre' => $identificacion['nombre_completo'] ?? '-',
            'legajo' => $identificacion['legajo'] ?? '-',
            'sede' => $identificacion['sede'] ?? '-',
            'jornada' => $identificacion['jornada_laboral'] ?? '-',
            'fecha_desde' => $this->formatDateForDisplay($fechaDesde),
            'fecha_hasta' => $this->formatDateForDisplay($fechaHasta),
            'dias' => $this->calculateCantidadDias($fechaDesde, $fechaHasta) ?? '-',
            'tipo_ausentismo' => $aviso['tipo_ausentismo_label'] ?? $aviso['tipo_ausentismo'] ?? '-',
            'nombre_familiar' => $aviso['nombre_familiar'] ?? null,
            'parentesco' => $aviso['parentesco'] ?? null,
            'motivo' => $aviso['motivo'] ?? '-',
            'domicilio_circunstancial' => $aviso['domicilio_circunstancial'] ?? null,
            'observaciones' => $aviso['observaciones'] ?? null,
        ];
    }

    public function buildConfirmationStepResult(Conversacion $conversation, array $avisoOverrides = []): StepResult
    {
        return StepResult::make(null, [
            'template' => config('medicina_laboral.mensajes.templates.aviso_confirmacion_final'),
            'template_data' => $this->buildConfirmationTemplateData($conversation, $avisoOverrides),
        ]);
    }

    public function createFromConversation(Conversacion $conversation): Aviso
    {
        $identificacion = Arr::get($conversation->metadata ?? [], 'identificacion', []);
        $aviso = Arr::get($conversation->metadata ?? [], 'aviso', []);
        $fechaDesde = $aviso['fecha_desde'] ?? null;
        $fechaHasta = $aviso['fecha_hasta'] ?? null;

        return Aviso::create([
            'conversacion_id' => $conversation->id,
            'dni' => $conversation->dni,
            'tipo' => 'inasistencia',
            'tipo_ausentismo' => $aviso['tipo_ausentismo'] ?? null,
            'fecha_inicio' => $fechaDesde,
            'fecha_fin' => $fechaHasta,
            'cantidad_dias' => $this->calculateCantidadDias($fechaDesde, $fechaHasta),
            'wa_number' => $conversation->wa_number,
            'nombre_completo' => $identificacion['nombre_completo'] ?? null,
            'legajo' => $identificacion['legajo'] ?? null,
            'sede' => $identificacion['sede'] ?? null,
            'jornada_laboral' => $identificacion['jornada_laboral'] ?? null,
            'motivo' => $aviso['motivo'] ?? null,
            'domicilio_circunstancial' => $aviso['domicilio_circunstancial'] ?? null,
            'observaciones' => $aviso['observaciones'] ?? null,
            'metadata' => [
                'identificacion' => $identificacion,
                'aviso' => $aviso,
            ],
        ]);
    }

    public function buildRegisteredTemplateData(Aviso $aviso): array
    {
        $periodo = trim(implode(' a ', array_filter([
            $aviso->fecha_inicio?->format('Y-m-d'),
            $aviso->fecha_fin?->format('Y-m-d'),
        ])));

        return [
            'numero_aviso' => $this->displayNumber($aviso),
            'nombre' => $aviso->nombre_completo ?? '-',
            'legajo' => $aviso->legajo ?? '-',
            'sede' => $aviso->sede ?? '-',
            'jornada' => $aviso->jornada_laboral ?? '-',
            'periodo' => $periodo !== '' ? $periodo : '-',
            'tipo_ausentismo' => $aviso->tipo_ausentismo ?? '-',
            'motivo' => $aviso->motivo ?? '-',
            'domicilio_circunstancial' => $aviso->domicilio_circunstancial ?? null,
            'deadline_horas' => (int) config('medicina_laboral.certificados.deadline_business_hours', 24),
        ];
    }

    public function displayNumber(Aviso $aviso): string
    {
        return 'AV-' . $aviso->id;
    }

    public function buildRegisteredStepResult(Aviso $aviso): StepResult
    {
        return StepResult::make(null, [
            'template' => config('medicina_laboral.mensajes.templates.aviso_registrado'),
            'template_data' => $this->buildRegisteredTemplateData($aviso),
        ]);
    }

    private function calculateCantidadDias(?string $fechaDesde, ?string $fechaHasta): ?int
    {
        if ($fechaDesde === null || $fechaHasta === null) {
            return null;
        }

        return Carbon::parse($fechaDesde)->diffInDays(Carbon::parse($fechaHasta)) + 1;
    }

    private function formatDateForDisplay(?string $date): string
    {
        if ($date === null || $date === '') {
            return '-';
        }

        return Carbon::parse($date)->format(config('medicina_laboral.avisos.input_date_format', 'd/m/Y'));
    }
}
