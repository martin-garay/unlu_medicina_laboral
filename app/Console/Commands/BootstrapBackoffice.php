<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\Backoffice\BackofficeBootstrapService;
use Illuminate\Console\Command;

class BootstrapBackoffice extends Command
{
    protected $signature = 'backoffice:bootstrap {--if-admin-missing}';

    protected $description = 'Sincroniza roles, permisos y el administrador inicial habilitado.';

    public function handle(BackofficeBootstrapService $bootstrap): int
    {
        if (
            $this->option('if-admin-missing')
            && config('backoffice.local_admin.enabled')
            && User::query()->where('email', config('backoffice.local_admin.email'))->exists()
        ) {
            $this->info('Backoffice bootstrap already present; no changes.');

            return self::SUCCESS;
        }

        $bootstrap->run();

        $this->info('Backoffice bootstrap applied.');

        return self::SUCCESS;
    }
}
