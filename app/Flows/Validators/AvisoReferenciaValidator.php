<?php

namespace App\Flows\Validators;

use App\Flows\Common\Contracts\Validator;
use App\Flows\Common\ValidationResult;
use App\Models\Aviso;
use App\Models\Conversacion;
use Illuminate\Support\Arr;

class AvisoReferenciaValidator implements Validator
{
    public function validate(Conversacion $conversation, array $input = []): ValidationResult
    {
        $raw = trim((string) ($input['text'] ?? ''));

        if ($raw === '') {
            return ValidationResult::invalid('required');
        }

        $avisoId = $this->extractAvisoId($raw);

        if ($avisoId === null) {
            return ValidationResult::invalid('aviso_inexistente');
        }

        $aviso = Aviso::query()->find($avisoId);

        if ($aviso === null) {
            return ValidationResult::invalid('aviso_inexistente');
        }

        if ($aviso->tipo !== 'inasistencia') {
            return ValidationResult::invalid('no_open_aviso');
        }

        $identificacion = Arr::get($conversation->metadata ?? [], 'identificacion', []);
        $legajo = $identificacion['legajo'] ?? null;

        if ($legajo !== null && $aviso->legajo !== null && (string) $aviso->legajo !== (string) $legajo) {
            return ValidationResult::invalid('aviso_no_corresponde_legajo');
        }

        $deadlineHours = (int) config('medicina_laboral.certificados.deadline_business_hours', 24);

        if ($deadlineHours > 0 && $aviso->created_at !== null && now()->greaterThan($aviso->created_at->copy()->addHours($deadlineHours))) {
            return ValidationResult::invalid('plazo_vencido_anticipo');
        }

        return ValidationResult::valid([
            'aviso_id' => $aviso->id,
            'numero_aviso' => 'AV-' . $aviso->id,
            'aviso_legajo' => $aviso->legajo,
            'aviso_tipo' => $aviso->tipo,
        ]);
    }

    private function extractAvisoId(string $raw): ?int
    {
        $normalized = mb_strtoupper(trim($raw));

        if (preg_match('/^AV-(\d+)$/', $normalized, $matches) === 1) {
            return (int) $matches[1];
        }

        if (preg_match('/^\d+$/', $normalized) === 1) {
            return (int) $normalized;
        }

        return null;
    }
}
