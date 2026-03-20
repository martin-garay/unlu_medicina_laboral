<?php

namespace App\Console\Commands;

use App\Services\ConversationTimeoutService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Throwable;

class ProcessConversationTimeouts extends Command
{
    protected $signature = 'conversations:process-timeouts
                            {--now= : Fecha y hora opcional para ejecutar el procesamiento en un instante fijo}';

    protected $description = 'Procesa conversaciones inactivas, envía recordatorios y cancela por timeout.';

    public function __construct(
        private readonly ConversationTimeoutService $conversationTimeoutService,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        try {
            $now = $this->option('now') !== null
                ? Carbon::parse((string) $this->option('now'))
                : null;
        } catch (Throwable) {
            $this->error('La opción --now debe ser una fecha/hora válida.');

            return self::FAILURE;
        }

        $summary = $this->conversationTimeoutService->process($now);

        $this->info(sprintf(
            'Conversaciones revisadas: %d | Elegibles: %d | Recordatorios enviados: %d | Canceladas: %d',
            $summary['checked'],
            $summary['eligible'],
            $summary['warning_1_sent'],
            $summary['cancelled'],
        ));

        return self::SUCCESS;
    }
}
