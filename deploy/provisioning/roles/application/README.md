# Rol application

Despliega Laravel con directorios `releases`, `shared` y symlink `current`.

`application_source_strategy` soporta dos valores implementados:

- `local`: sincroniza el workspace actual; pensado para Vagrant/desarrollo.
- `git`: clona un tag/SHA en la máquina de control y lo sincroniza al servidor; pensado para testing/producción.

La opción futura `artifact` queda reservada para un paquete generado por CI con checksum; no está implementada.

Administra Composer, `.env` desde Vault, storage compartido, permisos, migraciones, cachés y health check. No crea secretos ni administradores locales.

El rol instala `rsync` en el host remoto porque `ansible.posix.synchronize`
necesita `rsync` tanto en la estación de control como en el servidor. En
`--check`, si el paquete todavía no está disponible en el servidor, informa que
lo instalaría en apply real y saltea la sincronización para no bloquear el
dry-run inicial. La misma regla se aplica si el directorio de release todavía no
existe, porque en modo check las tareas previas sólo simulan su creación.
La sincronización remota usa `sudo -n rsync` y timeouts explícitos para fallar
con error visible si el sudo remoto o la conexión SSH no están listos, en lugar
de quedar esperando interacción.

La activación es transaccional a nivel de código: conserva el destino previo de
`current`, recarga PHP-FPM y valida `/up`. Ante un fallo restaura el symlink
anterior y finaliza con error. Las migraciones no se revierten automáticamente.
