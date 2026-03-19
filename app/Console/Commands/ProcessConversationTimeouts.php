<?php

namespace App\Console\Commands;

use App\Services\ConversationTimeoutService;
use Illuminate\Console\Command;

class ProcessConversationTimeouts extends Command
{
    protected $signature = 'conversations:process-timeouts';

    protected $description = 'Procesa conversaciones inactivas, envía recordatorios y cancela por timeout.';

    public function __construct(
        private readonly ConversationTimeoutService $conversationTimeoutService,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $summary = $this->conversationTimeoutService->process();

        $this->info(sprintf(
            'Conversaciones revisadas: %d | Recordatorios enviados: %d | Canceladas: %d',
            $summary['checked'],
            $summary['warning_1_sent'],
            $summary['cancelled'],
        ));

        return self::SUCCESS;
    }
}
