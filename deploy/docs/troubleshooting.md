# Troubleshooting de despliegue

## Método

Identificar primero dónde falla: control node, transporte SSH, host administrado
o aplicación. Conservar la primera excepción útil y evitar repetir un apply a
ciegas. Los comandos de este documento se ejecutan en **PC Uni**, dentro de
`deploy/provisioning`, salvo que se indique “servidor”.

## Ansible o Python incorrectos

Síntomas: filtros Jinja ausentes, `ansible.module_utils.six.moves`, warnings de
colecciones incompatibles o `import_playbook` rechazado pese a sintaxis válida.

```bash
bin/setup-control-node
export PATH="$PWD/bin:$PATH"
which ansible
ansible --version
bin/check-deploy
```

El Python recomendado es 3.10 o superior. Python 3.8 usa `ansible-core` 2.13 y
omite `ansible-lint`; sus warnings de `cryptography` no son por sí solos una
falla remota. Ejecutar el lint completo en un control node moderno antes de
integrar cambios.

## SSH, bastion o host key

```bash
ssh -G unlu-medicina-testing | grep -E '^(hostname|user|proxyjump|identityfile) '
ssh -o BatchMode=yes unlu-pc true
ssh -o BatchMode=yes unlu-medicina-testing true
ansible -i inventories/testing/hosts.yml app_servers -m ping -vv
```

Un timeout suele indicar VPN, ruta o firewall; `Permission denied` indica
identidad/usuario; `Host key verification failed` requiere revisar el cambio de
clave antes de aceptarlo. El bastion debe funcionar sin password interactiva.

## Proxy y Composer

Si host `curl` funciona pero Docker o Composer agotan el timeout:

```bash
env | grep -iE '^(http|https|no)_proxy='
docker run --rm -e http_proxy -e https_proxy -e no_proxy medicina-laboral-composer-control:latest curl -I https://api.github.com
docker run --rm -e http_proxy -e https_proxy -e no_proxy medicina-laboral-composer-control:latest curl -I https://repo.packagist.org/packages.json
```

Testing usa `application_composer_install_local: true`: las variables del shell
se propagan al build y al contenedor. No configurar el proxy institucional en
el servidor sólo para habilitar Composer; el diseño evita esa dependencia.

## Rsync y sudo

Para inventories que usan `application_transfer_strategy: rsync`:

```bash
ansible -i inventories/testing/hosts.yml app_servers -m command -a '/usr/bin/sudo -n /usr/bin/rsync --version' -e ansible_become=false
```

Un `rc=0` confirma binario y sudo no interactivo. Testing usa `archive` porque
rsync quedó esperando sobre el salto institucional; la transferencia por SFTP
es la estrategia validada allí.

## Checkout inexistente en check mode

Un checkout Git nuevo no se materializa con `--check`. El rol informa esa
condición y difiere la exigencia de `composer.json` al apply. Si falla en apply,
revisar tag remoto, URL, proxy y el directorio local bajo
`deploy/provisioning/.local/releases/`.

## Composer termina pero falla un script Laravel

`bootstrap/cache` y los directorios de `storage` deben existir y ser escribibles
dentro del bind mount. El rol los crea antes de `composer install`. No desactivar
scripts para ocultar el error: forman parte del artefacto validado.

Si aparece `Target class [Database\\Seeders\\...] does not exist`, recordar que
el autoload productivo usa `--no-dev`. Las operaciones requeridas en deploy deben
vivir en código productivo cargable, como `backoffice:bootstrap`, no depender de
seeders de desarrollo.

## Logs compartidos sin permisos

```bash
ansible -i inventories/testing/hosts.yml app_servers -b -m find -a 'paths=/var/www/medicina-laboral/shared/storage/logs file_type=file'
```

Laravel usa logs diarios `0664` y grupo compartido. Un error `StreamHandler ...
Permission denied` indica ownership/modo incorrectos o una sesión `deploy` que
aún no tomó su grupo suplementario. Reejecutar `application`; no aplicar
`chmod -R 777`.

## Driver de notificaciones no soportado

`MEDICINA_LABORAL_MAIL_DRIVER="null"` debe conservar comillas en el `.env` para
que Dotenv produzca la cadena `null`. El template Ansible es la fuente de verdad.
Después de corregirlo, desplegar un release nuevo y reconstruir cachés.

## Health check de Ansible falla por urllib3

El error `CustomHTTPSConnection ... cert_file` corresponde a incompatibilidades
del stack Python remoto. Los probes actuales usan `curl` en el servidor con
`--noproxy '*'`; si reaparece, el control node ejecuta código anterior.

Para separar aplicación de proxy/TLS, en el servidor:

```bash
curl --noproxy '*' -sS -o /dev/null -w 'HTTP=%{http_code}\n' https://avisos-pruebas.unlu.edu.ar/up
sudo readlink -f /var/www/medicina-laboral/current
```

Un 403 proviene normalmente de Apache/vhost; un 500 requiere revisar el log
diario Laravel; un 000 indica transporte/TLS. No activar manualmente un release
hasta validar `artisan`, `.env`, `storage` y `vendor`.

## `current` vacío, circular o inválido

```bash
sudo readlink /var/www/medicina-laboral/current
sudo readlink -f /var/www/medicina-laboral/current
sudo test -f /var/www/medicina-laboral/current/artisan
```

Usar el playbook de rollback con un release conocido. La automatización rechaza
destinos inexistentes o fuera de `releases/`; no improvisar enlaces multilínea
desde una terminal porque un salto puede convertir `/var/www/...` en `/`.

## Monitoring falla por backups

`redeploy` valida backups pero deliberadamente no los crea:

```bash
ansible-playbook -i inventories/testing/hosts.yml playbooks/backup.yml -e backup_run_now=true
ansible-playbook -i inventories/testing/hosts.yml playbooks/monitoring.yml
```

Si la antigüedad continúa excedida, revisar cron, rutas, checksums, reloj y
`monitoring_backup_max_age_seconds`; no ampliar el umbral sin una decisión de
RPO.

## Seguridad y firewall

Testing mantiene `security_enabled: false` y `firewall_enabled: false` hasta
preservar formalmente accesos institucionales existentes. No habilitar el tag
`security` como experimento remoto. Producción requiere redes explícitas y una
sesión de rescate disponible antes de aplicar política `drop`.

## Evidencia mínima para escalar

Adjuntar comando exacto, host lógico, versión de Ansible, tarea fallida, `rc`,
stderr pertinente, release activo y recap. Remover secretos, tokens, passwords,
payloads de WhatsApp y datos médicos.
