<?php

namespace Database\Seeders;

use App\Services\Backoffice\BackofficeBootstrapService;
use Illuminate\Database\Seeder;

class BackofficeRolesAndPermissionsSeeder extends Seeder
{
    public function run(BackofficeBootstrapService $bootstrap): void
    {
        $bootstrap->run();
    }
}
