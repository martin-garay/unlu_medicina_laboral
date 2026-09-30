# Problemas recurrentes

Revisado: 2026-09-30. Sólo entradas verificadas; detalles de deploy se consultan
en [troubleshooting canónico](../../deploy/docs/troubleshooting.md).

## Worktree sin directorios locales

- **Symptom:** Artisan/Composer no inicia o no escribe vistas/cache.
- **Cause:** `.gitignore` excluye `.env`, `vendor`, `bootstrap/*`, `storage/*`;
  el bind mount puede ocultar lo preparado por la imagen.
- **Solution:** preparar dependencias y directorios escribibles en ese worktree
  con UID/GID correctos; usar guía de inicio del README. No compartir caches
  ni resolver con permisos globales 777.
- **Related modules:** [.gitignore](../../.gitignore), [Dockerfile](../../docker/app/Dockerfile),
  [README](../../README.md), [testing](testing.md).

## Pruebas de otro checkout / puertos ocupados

- **Symptom:** cambios no aparecen en tests o falla un segundo `up`.
- **Cause:** app monta otro checkout; puertos fijos compartidos entre worktrees.
- **Solution:** verificar montaje/proyecto/SHA; Lead asigna entorno o serializa.
- **Related modules:** [Compose](../../docker-compose.yml), [Makefile](../../Makefile).

## Catálogo cambiado mientras una conversación está abierta

- **Symptom:** el admin renombra, reordena o desactiva una opción después de que
  el chat ya mostró el menú.
- **Cause:** la respuesta se interpreta con el catálogo vigente en vez del ofrecido.
- **Solution:** los menús de catálogos guardan una instantánea en metadata de la
  conversación; validadores resuelven contra esa instantánea y conservan el
  nombre mostrado. No eliminar opciones con historial asociado.
- **Related modules:** [SedeValidator](../../app/Flows/Validators/SedeValidator.php),
  [ConversationInteractionService](../../app/Services/Conversation/ConversationInteractionService.php),
  [inventario](../15-inventario-tablas-maestras.md).
