# Frontend

Revisado: 2026-09-27. Fuentes: [rutas web](../../routes/web.php),
[AdminPanelProvider](../../app/Providers/Filament/AdminPanelProvider.php),
[especificaciones del backoffice](../backoffice/module-specs.md).

- No hay SPA ni `package.json` versionado. No proponer pipeline Node por defecto.
- Chat `/internal/chat`: `InternalChatConsoleController` y
  `resources/views/internal_chat/console.blade.php`; sesión guarda transcripción
  y participante. Es herramienta local/interna, no una nueva UI productiva.
- Menús recibidos del motor; textos locales en `lang/es/internal_chat.php`.
- `/admin`: Filament, Resources en `app/Filament/Resources/` y vistas en
  `resources/views/filament/`; no existe un frontend independiente del backend.
- Permisos en `config/backoffice.php`, tablas Spatie y seed/bootstrap;
  [matriz canónica](../backoffice/permissions.md). Rol admin no elimina límites
  read-only de los Resources implementados.
- Tests de UI HTTP/Filament en `tests/Feature/Http/` y `tests/Feature/Backoffice/`;
  cobertura funcional no sustituye una inspección visual del cambio cuando aplique.

Separar ownership por Resource/vista. Models, permisos, traducciones y provider
son contratos/archivos potencialmente compartidos con un worker backend.
