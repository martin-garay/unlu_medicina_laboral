# Rol application

Despliega Laravel con directorios `releases`, `shared` y symlink `current`.

`application_source_strategy` soporta dos valores implementados:

- `local`: sincroniza el workspace actual; pensado para Vagrant/desarrollo.
- `git`: clona un tag/SHA en la máquina de control y lo sincroniza al servidor; pensado para testing/producción.

`application_transfer_strategy` soporta dos valores:

- `rsync`: sincroniza el árbol con `ansible.posix.synchronize`.
- `archive`: empaqueta el source en un `.tar.gz`, lo copia por SSH con Ansible y
  lo extrae en el release remoto.

La opción futura `artifact` queda reservada para un paquete generado por CI con checksum; no está implementada.

`application_composer_install_local` controla dónde se descargan las
dependencias de producción:

- `false`: Composer se ejecuta en el host administrado.
- `true`: la estación de control construye `vendor/` dentro de un contenedor
  creado con `docker/app/Dockerfile` del checkout exacto seleccionado por
  `application_git_ref`, y luego lo sincroniza por rsync al
  `application_release_path` correspondiente.

El modo local existe para entornos sin salida HTTPS a Packagist/GitHub. No
depende del PHP instalado en la estación de control: usa el PHP del Dockerfile
versionado junto al release. Después de transferir `vendor/`, el host remoto
ejecuta `composer check-platform-reqs --no-dev` antes de migrar o activar el
release. Python local y remoto no necesitan compartir versión; sólo deben ser
compatibles con sus respectivos lados de Ansible.

La sincronización de `vendor/` invoca explícitamente
`/usr/bin/sudo -n /usr/bin/rsync` en el destino. Esto evita depender del `PATH`
de la sesión no interactiva y exige el mismo privilegio sin contraseña que se
valida antes de transferir.

Cuando la estación de control requiere proxy, el rol toma `http_proxy`,
`https_proxy` y `no_proxy` de su entorno (con fallback a las variantes en
mayúsculas) y las propaga tanto a `docker build` como al contenedor que ejecuta
Composer. La URL institucional no se hardcodea ni se guarda en el inventory. Si
el proxy incorpora credenciales, deben inyectarse en el entorno operativo desde
un mecanismo seguro y nunca versionarse.

Antes de ejecutar Composer, el rol crea en el checkout local `bootstrap/cache`
y los subdirectorios escribibles de `storage/`. El bind mount del checkout
oculta los directorios creados dentro de la imagen Docker; sin esta preparación,
los scripts de Composer de Laravel/Filament fallan aunque las dependencias ya se
hayan descargado.

Administra Composer, `.env` desde Vault, storage compartido, permisos, migraciones, cachés y health check. No crea secretos ni administradores locales.

El rol instala `rsync` en el host remoto porque `ansible.posix.synchronize`
necesita `rsync` tanto en la estación de control como en el servidor. En
`--check`, si el paquete todavía no está disponible en el servidor, informa que
lo instalaría en apply real y saltea la sincronización para no bloquear el
dry-run inicial. La misma regla se aplica si el directorio de release todavía no
existe, porque en modo check las tareas previas sólo simulan su creación.
La sincronización remota usa `sudo -n rsync` y un timeout de inactividad para
fallar con error visible si el sudo remoto o la transferencia no están listos,
en lugar de quedar esperando interacción.
Antes de sincronizar, el rol valida que `rsync` exista en la estación de control,
que el source resuelto exista y contenga `composer.json`, y que el host remoto
pueda ejecutar `sudo -n rsync --version`.
Por defecto, antes de sincronizar limpia el directorio del release si existe pero
no es el release activo apuntado por `current`; esto permite reintentar un deploy
fallido sin conservar archivos excluidos o parciales de intentos anteriores.
En testing se usa `archive` para evitar esperas opacas de `synchronize` sobre el
salto SSH institucional.

La activación es transaccional a nivel de código: conserva el destino previo de
`current`, recarga PHP-FPM y valida `/up`. Ante un fallo restaura el symlink
anterior y finaliza con error. Las migraciones no se revierten automáticamente.
