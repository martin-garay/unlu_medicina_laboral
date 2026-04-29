<?php

$permissions = [
    'backoffice.access',
    'dashboard.view',
    'users.view',
    'users.manage',
    'roles.view',
    'roles.manage',
    'avisos.view',
    'certificados.view',
    'conversaciones.view',
    'conversaciones.historial.view',
    'auditoria.view',
    'reportes.view',
];

return [
    'guard' => env('BACKOFFICE_GUARD', 'web'),

    'permissions' => $permissions,

    'roles' => [
        'admin' => $permissions,
        'auditor' => [
            'backoffice.access',
            'dashboard.view',
            'avisos.view',
            'certificados.view',
            'conversaciones.view',
            'conversaciones.historial.view',
            'auditoria.view',
            'reportes.view',
        ],
        'director' => [
            'backoffice.access',
            'dashboard.view',
            'avisos.view',
            'certificados.view',
            'conversaciones.view',
            'conversaciones.historial.view',
            'reportes.view',
        ],
    ],

    'local_admin' => [
        'enabled' => env('BACKOFFICE_LOCAL_ADMIN_ENABLED', false),
        'name' => env('BACKOFFICE_LOCAL_ADMIN_NAME', 'Admin'),
        'email' => env('BACKOFFICE_LOCAL_ADMIN_EMAIL', 'admin@admin.com'),
        'password' => env('BACKOFFICE_LOCAL_ADMIN_PASSWORD', 'admin123456'),
        'role' => 'admin',
    ],
];
