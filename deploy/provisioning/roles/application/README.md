# Rol application

Despliega Laravel con directorios `releases`, `shared` y symlink `current`.

En Vagrant sincroniza el workspace local a un release estable para permitir iteración e idempotencia. La interfaz `application_source_strategy` queda preparada para agregar releases Git por tag/SHA en producción sin modificar roles tecnológicos.

Administra Composer, `.env` desde Vault, storage compartido, permisos, migraciones, cachés y health check. No crea secretos ni administradores locales.

La activación es transaccional a nivel de código: conserva el destino previo de
`current`, recarga PHP-FPM y valida `/up`. Ante un fallo restaura el symlink
anterior y finaliza con error. Las migraciones no se revierten automáticamente.
