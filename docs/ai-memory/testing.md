# Testing y entorno local

Revisado: 2026-09-27. Fuentes: [Makefile](../../Makefile),
[phpunit.xml](../../phpunit.xml), [TestCase](../../tests/TestCase.php),
[política de testing](../11-testing-y-criterios.md), [Compose](../../docker-compose.yml).

## Comandos existentes

- `make test`, `make test-unit`, `make test-feature`: contenedor app existente.
- `make artisan CMD='test --filter NombreDelTest'`: prueba dirigida.
- `git diff --check`: formato del diff; no valida enlaces Markdown.
- `make diagrams`: regenera; `make diagrams-check`: regenera y compara con Git.
  Ambos escriben archivos. Ownership de fuentes y derivados conjunto.
- Checks específicos de deploy: consultar [deploy/README](../../deploy/README.md).
  `check-docs` de provisioning cubre deploy, no todos los docs del repositorio.
- No hay pipeline CI/CD versionado identificado. Resultados locales deben indicar
  comando, SHA, entorno y límites; no afirmar que hubo CI remoto.

## Aislamiento de tests y worktrees

`TestCase` impone SQLite `:memory:` y reconecta por test; `phpunit.xml` fija cache
array. `CreatesTestingSchema` elimina/recrea tablas sobre esa conexión.
No apuntar estas pruebas a una DB compartida. Tests de PostgreSQL/migración real
necesitan DB de prueba explícita y ownership exclusivo.

Compose publica 8000/5432 fijos y monta el checkout. `-p` distingue proyecto,
redes y volúmenes, pero no arregla colisiones de puertos. `make test` prueba el
checkout montado en app, no necesariamente el directorio desde donde se invoca.
El Lead verifica bind mount/SHA y serializa up/down/restart/migrate/scheduler.

Para workers: dependencias, bootstrap, storage y caches por worktree. No compartir
por symlink directorios mutables. Composer tiene post-script `filament:upgrade`;
puede generar assets. El worker reporta también esos cambios y no los integra
fuera del scope sin coordinación.

Opciones: runtime aislado preparado por Lead con puertos/volúmenes propios, o
contenedor efímero sin puertos/dependencias de servicios para PHPUnit; verificar
montaje del worktree y SQLite antes. Si no se prepara ese aislamiento, workers
escriben tests y el Lead los ejecuta serialmente sobre la integración. No hay
orquestador automático de entornos instalado por el workflow documental.

No repetir suite completa tras cada edición documental. Ejecutar pruebas
relevantes del cambio y regresión del conjunto cuando haya integración/cambios
nuevos, no para reproducir sin motivo evidencia ya válida.
