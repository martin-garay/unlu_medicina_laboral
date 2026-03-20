<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

class OperationalDoctor extends Command
{
    protected $signature = 'medicina:doctor';

    protected $description = 'Verifica configuración y dependencias operativas mínimas del entorno actual.';

    public function handle(): int
    {
        $checks = [
            $this->checkAppKey(),
            $this->checkDatabase(),
            $this->checkLogging(),
            $this->checkWhatsAppConfig(),
            $this->checkTimeoutConfig(),
            $this->checkStorageConfig(),
        ];

        foreach ($checks as $check) {
            $this->line(sprintf('[%s] %s: %s', strtoupper($check['status']), $check['label'], $check['detail']));
        }

        $errors = collect($checks)->where('status', 'error')->count();
        $warnings = collect($checks)->where('status', 'warn')->count();

        if ($errors > 0) {
            $this->error(sprintf('Diagnóstico operativo fallido. Errores: %d | Advertencias: %d', $errors, $warnings));

            return self::FAILURE;
        }

        if ($warnings > 0) {
            $this->warn(sprintf('Diagnóstico operativo OK con advertencias. Advertencias: %d', $warnings));

            return self::SUCCESS;
        }

        $this->info('Diagnóstico operativo OK sin observaciones.');

        return self::SUCCESS;
    }

    private function checkAppKey(): array
    {
        $key = (string) config('app.key', '');

        if ($key === '') {
            return $this->errorCheck('app_key', 'APP_KEY no está configurada.');
        }

        return $this->okCheck('app_key', 'APP_KEY configurada.');
    }

    private function checkDatabase(): array
    {
        try {
            DB::connection()->select('select 1');
        } catch (Throwable $exception) {
            return $this->errorCheck('database', 'No se pudo conectar a la base de datos: '.$exception->getMessage());
        }

        return $this->okCheck('database', 'Conexión a base de datos disponible.');
    }

    private function checkLogging(): array
    {
        $channel = (string) config('logging.default', 'stack');
        $level = (string) config('logging.channels.'.$channel.'.level', config('logging.channels.single.level', 'debug'));

        if ($channel !== 'stderr') {
            return $this->warnCheck('logging', sprintf('LOG_CHANNEL actual: %s (para Docker se recomienda stderr). Nivel: %s.', $channel, $level));
        }

        return $this->okCheck('logging', sprintf('Canal de logs actual: %s. Nivel: %s.', $channel, $level));
    }

    private function checkWhatsAppConfig(): array
    {
        $verifyToken = (string) env('WHATSAPP_VERIFY_TOKEN', '');
        $token = (string) env('WHATSAPP_TOKEN', '');
        $phoneId = (string) env('WHATSAPP_PHONE_ID', '');

        if ($verifyToken === '') {
            return $this->errorCheck('whatsapp', 'WHATSAPP_VERIFY_TOKEN no está configurado.');
        }

        if ($token === '' || $phoneId === '' || str_contains($token, 'your_') || str_contains($phoneId, 'your_')) {
            return $this->warnCheck('whatsapp', 'Faltan credenciales salientes completas de WhatsApp Cloud API.');
        }

        return $this->okCheck('whatsapp', 'Verificación y credenciales salientes configuradas.');
    }

    private function checkTimeoutConfig(): array
    {
        $first = (int) config('medicina_laboral.conversation.first_inactivity_minutes', 0);
        $second = (int) config('medicina_laboral.conversation.second_inactivity_minutes', 0);
        $action = (string) config('medicina_laboral.conversation.second_inactivity_action', '');

        if ($first <= 0 || $second <= 0) {
            return $this->errorCheck('timeouts', 'Los umbrales de inactividad deben ser mayores que cero.');
        }

        if ($second < $first) {
            return $this->errorCheck('timeouts', 'El segundo umbral no puede ser menor que el primero.');
        }

        if (!in_array($action, ['cancel'], true)) {
            return $this->errorCheck('timeouts', sprintf('La acción del segundo umbral no es válida: %s.', $action));
        }

        return $this->okCheck('timeouts', sprintf('Umbrales %d/%d minutos con acción final %s.', $first, $second, $action));
    }

    private function checkStorageConfig(): array
    {
        $draftDriver = (string) config('medicina_laboral.storage.draft_driver', '');
        $finalDriver = (string) config('medicina_laboral.storage.final_driver', '');
        $finalDisk = (string) config('medicina_laboral.storage.final_attachments.disk', '');

        if ($draftDriver === '' || $finalDriver === '' || $finalDisk === '') {
            return $this->errorCheck('storage', 'La configuración de storage de adjuntos está incompleta.');
        }

        return $this->okCheck('storage', sprintf(
            'Draft driver: %s | Final driver: %s | Final disk: %s.',
            $draftDriver,
            $finalDriver,
            $finalDisk
        ));
    }

    private function okCheck(string $label, string $detail): array
    {
        return ['status' => 'ok', 'label' => $label, 'detail' => $detail];
    }

    private function warnCheck(string $label, string $detail): array
    {
        return ['status' => 'warn', 'label' => $label, 'detail' => $detail];
    }

    private function errorCheck(string $label, string $detail): array
    {
        return ['status' => 'error', 'label' => $label, 'detail' => $detail];
    }
}
