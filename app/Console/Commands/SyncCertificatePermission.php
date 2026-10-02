<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class SyncCertificatePermission extends Command
{
    protected $signature = 'certificates:permissions';

    protected $description = 'Agrega permiso de descarga al admin sin alterar roles personalizados';

    public function handle(): int
    {
        $guard = config('backoffice.guard', 'web');
        $p = Permission::findOrCreate('certificados.download', $guard);
        $admin = Role::where('name', 'admin')->where('guard_name', $guard)->first();
        if ($admin && ! $admin->hasPermissionTo($p)) {
            $admin->givePermissionTo($p);
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return self::SUCCESS;
    }
}
