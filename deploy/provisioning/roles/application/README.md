# Rol application

Despliega Laravel con directorios `releases`, `shared` y symlink `current`.

`application_source_strategy` soporta dos valores implementados:

- `local`: sincroniza el workspace actual; pensado para Vagrant/desarrollo.
- `git`: clona un tag/SHA en la máquina de control y lo sincroniza al servidor; pensado para testing/producción.

La opción futura `artifact` queda reservada para un paquete generado por CI con checksum; no está implementada.

Administra Composer, `.env` desde Vault, storage compartido, permisos, migraciones, cachés y health check. No crea secretos ni administradores locales.

La activación es transaccional a nivel de código: conserva el destino previo de
`current`, recarga PHP-FPM y valida `/up`. Ante un fallo restaura el symlink
anterior y finaliza con error. Las migraciones no se revierten automáticamente.
