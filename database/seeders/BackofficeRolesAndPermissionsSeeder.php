<?php

namespace Database\Seeders;

use App\Domain\Auditoria\Services\AuditoriaAdministrativaService;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class BackofficeRolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $guard = config('backoffice.guard', 'web');

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (config('backoffice.permissions', []) as $permission) {
            Permission::query()->updateOrCreate(
                ['name' => $permission, 'guard_name' => $guard],
                ['name' => $permission, 'guard_name' => $guard],
            );
        }

        foreach (config('backoffice.roles', []) as $roleName => $permissions) {
            $role = Role::query()->updateOrCreate(
                ['name' => $roleName, 'guard_name' => $guard],
                ['name' => $roleName, 'guard_name' => $guard],
            );

            $role->syncPermissions($permissions);
        }

        $this->seedLocalAdmin($guard);
        $this->recordAuditEvents($guard);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    private function seedLocalAdmin(string $guard): void
    {
        if (! config('backoffice.local_admin.enabled')) {
            return;
        }

        $roleName = config('backoffice.local_admin.role', 'admin');

        $user = User::query()->updateOrCreate(
            ['email' => config('backoffice.local_admin.email')],
            [
                'name' => config('backoffice.local_admin.name'),
                'password' => config('backoffice.local_admin.password'),
                'is_admin' => true,
            ],
        );

        $user->assignRole(Role::findByName($roleName, $guard));
    }

    private function recordAuditEvents(string $guard): void
    {
        if (! Schema::hasTable('auditoria_administrativa')) {
            return;
        }

        $audit = app(AuditoriaAdministrativaService::class);

        $audit->record(
            action: 'permissions.seeded',
            origin: AuditoriaAdministrativaService::ORIGIN_COMMAND,
            metadata: [
                'guard' => $guard,
                'permissions_count' => count(config('backoffice.permissions', [])),
            ],
        );

        $audit->record(
            action: 'roles.seeded',
            origin: AuditoriaAdministrativaService::ORIGIN_COMMAND,
            metadata: [
                'guard' => $guard,
                'roles_count' => count(config('backoffice.roles', [])),
            ],
        );
    }
}
