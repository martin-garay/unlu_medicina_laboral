# Runbook operativo

## Alcance y convenciones

Este documento es la fuente canónica para operar deployments ya preparados.
La preparación del control node, SSH, inventarios y Vault está en
[`deployment-guide.md`](deployment-guide.md). Producción exige además los
controles de [`production-deployment.md`](production-deployment.md).

Salvo indicación contraria, todos los comandos se ejecutan en **PC Uni**, desde
`deploy/provisioning`, con el venv preparado y `bin/` en `PATH`:

```bash
cd deploy/provisioning
export PATH="$PWD/bin:$PATH"
```

No reutilizar un tag Git publicado. Cada release debe identificar exactamente
un commit y usar un `application_release_id` nuevo. No ejecutar desde una copia
del repositorio con cambios locales no revisados.

## Preflight de PC Uni

```bash
git status --short
git fetch --tags origin
ansible --version
ansible-galaxy collection list
test -r "$HOME/.config/medicina-laboral/ansible-vault-password"
ssh -o BatchMode=yes unlu-pc true
ssh -o BatchMode=yes unlu-medicina-testing true
ansible-inventory -i inventories/testing/hosts.yml --graph
ansible -i inventories/testing/hosts.yml app_servers -m ping
ansible -i inventories/testing/hosts.yml app_servers -m command -a '/usr/bin/sudo -n true' -e ansible_become=false
bin/check-deploy
```

Confirmar además que el shell exporta `http_proxy`, `https_proxy` y `no_proxy`
cuando la red institucional los requiere. No guardar credenciales de proxy en
el inventory ni en Git.

## Publicación de un release inmutable

En una estación autorizada para publicar Git:

```bash
git status --short
git tag -a testing-AAAA-MM-DD-NN -m "Testing AAAA-MM-DD NN" COMMIT
git push origin testing-AAAA-MM-DD-NN
git ls-remote --tags origin testing-AAAA-MM-DD-NN
```

Luego actualizar `application_release_id` en el inventory de testing. El
`application_git_ref` deriva de ese identificador. Si el tag ya existe y su
contenido es incorrecto, crear otro; no moverlo ni reutilizar el directorio de
un release activo.

## Matriz de comandos

| Intención | Comando en PC Uni |
|---|---|
| Validar código de deploy | `bin/check-deploy` |
| Inspeccionar inventory | `ansible-inventory -i inventories/testing/hosts.yml --graph` |
| Check completo | `ansible-playbook -i inventories/testing/hosts.yml site.yml --check --diff` |
| Apply completo | `ansible-playbook -i inventories/testing/hosts.yml site.yml` |
| Ver tags | `ansible-playbook -i inventories/testing/hosts.yml site.yml --list-tags` |
| Ver alcance de redeploy | `ansible-playbook -i inventories/testing/hosts.yml site.yml --tags redeploy --list-tasks` |
| Check de redeploy | `ansible-playbook -i inventories/testing/hosts.yml site.yml --tags redeploy --check --diff` |
| Apply de redeploy | `ansible-playbook -i inventories/testing/hosts.yml site.yml --tags redeploy` |
| Capacidad individual | `ansible-playbook -i inventories/testing/hosts.yml site.yml --tags TAG --check --diff` |
| Bootstrap inicial | `ansible-playbook -i inventories/testing/hosts.yml playbooks/bootstrap-access.yml` |
| Backup inmediato | `ansible-playbook -i inventories/testing/hosts.yml playbooks/backup.yml -e backup_run_now=true` |
| Restore no destructivo | `ansible-playbook -i inventories/testing/hosts.yml playbooks/restore-test.yml` |
| Diagnóstico | `ansible-playbook -i inventories/testing/hosts.yml playbooks/monitoring.yml` |
| Rollback de código | `ansible-playbook -i inventories/testing/hosts.yml playbooks/rollback.yml -e rollback_release_id=RELEASE_ANTERIOR` |

Los tags admitidos son `validate`, `common`, `database`, `runtime`,
`application`, `scheduler`, `tls`, `backup`, `security`, `monitoring` y
`redeploy`. No existe `deploy`: ejecutar `site.yml` sin tags ya representa el
recorrido completo. No hay tags directos de migración, restart o borrado.

## Deploy a testing

1. Completar el preflight y revisar el diff del inventory.
2. Confirmar que el tag remoto coincide con `application_release_id`.
3. Ejecutar check completo o `redeploy --check --diff`, según el alcance.
4. Leer la lista efectiva de cambios. Un check no crea el checkout, release,
   backup ni servicios que todavía no existen.
5. Antes de migraciones, generar un backup inmediato y ejecutar monitoring.
6. Ejecutar el apply con exactamente el mismo inventory y selección de tags.
7. Repetir el apply; debe converger con `changed=0`, salvo una causa explícita.
8. Ejecutar monitoring y las verificaciones posteriores.

En testing, PC Uni clona por HTTPS, construye `vendor/` con Docker usando el
Dockerfile del tag, propaga el proxy del shell, crea artefactos `.tar.gz` y los
transfiere por SFTP. El servidor valida requisitos de plataforma, enlaza `.env`
y `storage` compartidos, migra, optimiza cachés, activa `current`, recarga
PHP-FPM sólo si cambió el enlace y prueba `/up`.

## Verificación posterior

### Proxy persistente de WhatsApp y prueba con Meta

Testing define `application_whatsapp_proxy_unlu` en inventory; el template genera
`WHATSAPP_PROXY_UNLU` y `config/medicina_laboral.php` lo expone al sender. Éste
usa el proxy explícito para texto y menús, sin desactivar TLS. No depende del
entorno del shell ni modifica PHP-FPM globalmente. Vacío conserva defaults HTTP.
No confundirlo con el proxy Docker/Composer del control node.

Este cambio requiere código nuevo: no reutilizar `testing-2026-09-07-04`.
Después de publicar el commit revisado, crear y publicar un tag único desde una
estación autorizada, sin cambios locales:

```bash
git pull --ff-only origin main
git status --short
git diff --name-status testing-2026-09-07-04..HEAD -- database/migrations
# Revisar también los demás cambios de producto incluidos en el nuevo release.
git tag -a testing-2026-10-01-01 -m "Testing proxy WhatsApp UNLu" HEAD
git push origin testing-2026-10-01-01
```

Si el tag existe, elegir otro; no moverlo. En PC Uni, desde la raíz del repo:

```bash
git pull --ff-only origin main
git fetch --tags origin
cd deploy/provisioning
export PATH="$PWD/bin:$PATH"
bin/check-deploy
ansible -i inventories/testing/hosts.yml app_servers -m ping
ansible-playbook -i inventories/testing/hosts.yml playbooks/backup.yml -e backup_run_now=true
ansible-playbook -i inventories/testing/hosts.yml playbooks/monitoring.yml
ansible-playbook -i inventories/testing/hosts.yml site.yml --tags redeploy --check --diff -e application_release_id=testing-2026-10-01-01
# Detenerse ante errores; el check no ejecuta las migraciones ni prueba el release.
ansible-playbook -i inventories/testing/hosts.yml site.yml --tags redeploy -e application_release_id=testing-2026-10-01-01
curl --noproxy '*' -sS --fail https://avisos-pruebas.unlu.edu.ar/up
```

El override `-e` prevalece sobre ambas declaraciones del release en inventory.
Antes del apply revisar todas las migraciones pendientes: el release contiene
los cambios de producto entre el tag anterior y HEAD, no sólo el proxy.
El tag `redeploy` no instala certificados; sí migra DB, optimiza config y activa
el release. Registrar después el release aplicado en ambas entradas del inventory.

En el servidor, comprobar sin imprimir secretos:

```bash
cd /var/www/medicina-laboral/current
php artisan tinker --execute="dump(config('medicina_laboral.whatsapp.proxy_unlu'));"
```

En Meta configurar el callback
`https://avisos-pruebas.unlu.edu.ar/api/whatsapp/webhook`, usar el verify token
de la Vault del entorno, verificar y guardar, y suscribir `messages`. No publicar
el token. Enviar un mensaje desde un teléfono de prueba autorizado al número
configurado y comprobar respuesta en el teléfono y logs del servidor.
La prueba por webhook valida el sender desde PHP-FPM. No registrar el milestone
como validado end-to-end hasta completar ese envío. Una ACL de entrada o un token
expirado pueden impedir la prueba aunque el proxy y TLS funcionen.

### Activar los certificados institucionales de testing

Ejecutar en PC Uni después de publicar el commit de configuración. Este corte
usa sólo `tls`: conserva el release aplicativo, no migra DB y no requiere tag
nuevo de aplicación. No ejecutar un apply completo para esta operación.

```bash
cd ~/programas/unlu_medicina_laboral
git status --short
# Si hay cambios locales, resolverlos antes del pull; no descartarlos.
git pull --ff-only origin main
cd deploy/provisioning
export PATH="$PWD/bin:$PATH"
bin/check-deploy
ansible -i inventories/testing/hosts.yml app_servers -m ping
ansible -i inventories/testing/hosts.yml app_servers -b -m command -a 'apachectl configtest'
ansible-playbook -i inventories/testing/hosts.yml site.yml --tags tls --list-tasks
ansible-playbook -i inventories/testing/hosts.yml site.yml --tags tls --check --diff
# Revisar el check: no debe proponer copiar certificados ni claves.
ansible-playbook -i inventories/testing/hosts.yml site.yml --tags tls
ansible-playbook -i inventories/testing/hosts.yml site.yml --tags tls
curl --noproxy '*' --connect-timeout 10 --max-time 30 -sS -o /dev/null -w 'HTTP=%{http_code}\n' https://avisos-pruebas.unlu.edu.ar/up
```

Detenerse ante un check fallido. `provided` sólo inspecciona los archivos de
infraestructura; Apache cambia las rutas de su VirtualHost, valida configuración
y recarga mediante handler. Repetir debe converger sin cambios. La prueba HTTPS
no usa `-k` ni nuestra CA local. Repetirla desde una red externa autorizada;
las ACL de entrada pueden impedir una prueba desde otras IPs.

Las rutas son `/etc/letsencrypt/live/avisos-pruebas.unlu.edu.ar/fullchain.pem`
y `/etc/letsencrypt/live/avisos-pruebas.unlu.edu.ar/privkey.pem`.
Confirmar con infraestructura la renovación y recarga automática de Apache.
Este corte no persiste el proxy de salida del runtime ni valida WhatsApp real.

Desde PC Uni:

```bash
curl -k -sS -o /dev/null -w 'UP=%{http_code}\n' https://avisos-pruebas.unlu.edu.ar/up
curl -k -sS -o /dev/null -w 'CHAT=%{http_code}\n' https://avisos-pruebas.unlu.edu.ar/internal/chat
curl -k -sS -o /dev/null -w 'ADMIN=%{http_code}\n' https://avisos-pruebas.unlu.edu.ar/admin
```

El `-k` sólo compensa la CA local de testing. No es aceptable para validar el
certificado productivo. En el servidor de testing, después de abrir una sesión
SSH manual:

```bash
sudo readlink -f /var/www/medicina-laboral/current
cd /var/www/medicina-laboral/current && php artisan migrate:status
cd /var/www/medicina-laboral/current && php artisan medicina:doctor
sudo find /var/www/medicina-laboral/shared/storage/logs -maxdepth 1 -type f -printf '%M %u:%g %p\n'
```

`/up` debe responder 200. Chat y admin pueden redirigir a autenticación, pero no
deben devolver 500. Los logs deben ser escribibles por el grupo compartido.

## Idempotencia, releases fallidos y rollback

Una segunda ejecución del mismo alcance debe terminar sin cambios. Investigar
todo `changed` persistente antes de promover a producción.

La activación restaura automáticamente el symlink previo si el health check del
nuevo release falla. Un release incompleto que no está activo puede limpiarse y
reintentarse mediante el rol; nunca borrar manualmente `current` ni el release
anterior durante un apply.

No hay poda automática de releases de código. Conservar al menos el activo y un
anterior validado; revisar capacidad y acordar una política antes de eliminar
otros directorios. Los checkouts y artefactos locales bajo `deploy/.local/` y
`deploy/provisioning/.local/` son estado de trabajo, no backups.

El rollback dedicado cambia código, reconstruye cachés, recarga PHP-FPM y valida
salud. No revierte migraciones. Antes de desplegar una migración incompatible se
requiere un procedimiento específico, backup verificado y ventana aprobada.

## Backups, restore y retención

Los backups viven inicialmente en `/var/backups/medicina-laboral`, en ramas
`postgresql` y `files`, con checksums y retención diaria/semanal/mensual. El
restore-test crea una base temporal y sólo inspecciona el archivo persistente;
no sobrescribe la base ni el storage activos.

`redeploy` ejecuta monitoring pero no genera backups. Si no hay una copia
reciente, monitoring falla correctamente: ejecutar primero el playbook dedicado.
La segunda copia institucional, RPO/RTO y responsables siguen siendo decisiones
previas a producción.

## Administrador bootstrap de testing

Testing puede crear una cuenta inicial cuando el inventory habilita
`application_backoffice_admin_enabled`. El correo está en el inventory y la
contraseña sólo en Vault. La operación es idempotente por correo: no rota la
contraseña de una cuenta existente. Después del primer acceso, la rotación debe
realizarse mediante un procedimiento administrativo aprobado. Producción tiene
esta función deshabilitada por defecto.

## Cierre de una intervención

Registrar release, alcance, recaps, endpoints, backup utilizado y cualquier
decisión humana. No pegar secretos, payloads médicos ni contenido de Vault en
issues o logs de sesión. Si el procedimiento difiere del código, detenerse y
corregir primero la documentación o la automatización.
