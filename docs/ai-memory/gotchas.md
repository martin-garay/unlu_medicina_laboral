# Problemas recurrentes

Revisado: 2026-09-27. Sólo entradas verificadas; detalles de deploy se consultan
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

## Catálogo y botones fuera de sincronía

- **Symptom:** selección numérica/interactiva no corresponde a etiqueta esperada.
- **Cause:** validadores de sede/jornada/tipos vinculan botones y catálogo por índice.
- **Solution:** mantener orden coherente y probar texto/ID; para normalización
  futura consultar CAT-001, no rediseñar durante un cambio ajeno.
- **Related modules:** [SedeValidator](../../app/Flows/Validators/SedeValidator.php),
  [inventario](../15-inventario-tablas-maestras.md).
