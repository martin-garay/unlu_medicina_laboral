# Plan de despliegue con Ansible

## Propósito y estado

Este documento es la fuente de verdad técnica para planificar el despliegue de Medicina Laboral UNLu con Ansible. Está basado en el repositorio relevado el 2026-08-04 y separa explícitamente:

- **actual**: comportamiento comprobado en archivos existentes;
- **recomendado**: diseño propuesto para implementar en etapas posteriores;
- **pendiente**: decisión o dependencia que requiere confirmación.

Esta etapa no implementa Ansible, Vagrant ni configuración productiva. El entorno Docker local permanece sin cambios.

## Decisiones acordadas el 2026-08-04

- La topología objetivo inicial usa **dos servidores**, uno en `app_servers` y otro en `db_servers`.
- La misma automatización debe admitir un servidor único asignando la misma IP/host a ambos grupos.
- Los defaults iniciales usarán **Debian 13 `trixie`**. Ubuntu 24.04 se incorporará como otro proveedor/versión seleccionable, sin mezclar sus tareas con Debian.
- Los defaults iniciales serán **PHP 8.4** y **PostgreSQL 17**, versiones incluidas por Debian 13, evitando inicialmente repositorios de paquetes externos.
- Dominio, DNS y TLS serán variables por entorno; no se fija todavía un dominio real.
- El acceso de Ansible será por SSH con clave. Se soportará conexión directa y bastion opcional mediante variables de inventory.
- Los secretos se cifrarán con Ansible Vault. Inicialmente la contraseña de Vault estará fuera del repositorio, en `~/.config/medicina-laboral/ansible-vault-password`, con permisos `0600`.
- El storage privado inicial recomendado será filesystem local fuera de `public/`, en `/var/lib/medicina-laboral/private`, enlazado/configurado como disco privado de Laravel cuando la aplicación implemente persistencia real.
- Los backups se generarán inicialmente bajo `/var/backups/medicina-laboral`, con subdirectorios para PostgreSQL, archivos y manifiestos. Esa ruta local no reemplaza una copia externa.
- El release se identificará por tag o commit de Git. En la primera etapa el código se obtendrá del remoto `origin` de GitHub mediante credencial SSH de solo lectura; una CI podrá producir artefactos inmutables más adelante.
- Se adopta una política mínima de logs sin payloads completos ni secretos, con rotación local, y monitoreo inicial basado en checks de sistema más alertas configurables.
- Para Vagrant se usará `medicina-laboral.test` como hostname local configurable, HTTPS con una CA local y ngrok como URL pública temporal del webhook.
- Vagrant ingresará inicialmente con su usuario de bootstrap y Ansible creará `deploy`, con clave SSH y sudo acotado; las ejecuciones normales posteriores usarán `deploy`.
- Bastion queda deshabilitado: no es necesario para Vagrant ni para una conexión SSH directa.
- Las credenciales actuales de Meta se conservarán y se cargarán en el Vault cifrado, sin copiarlas en inventarios planos ni documentación.
- La contraseña de Vault fue creada el 2026-08-05 en `/home/mgaray/.config/medicina-laboral/ansible-vault-password` con permisos `0600`; su contenido no se versiona.

Estas decisiones permiten comenzar la estructura base y las pruebas locales. Dominio/TLS, destino externo de backups y canal de alertas pueden permanecer variables hasta sus etapas específicas.

### URLs diferenciadas

No debe existir una única variable ambigua para todas las URLs:

```yaml
application_hostname: medicina-laboral.test
application_url: https://medicina-laboral.test
whatsapp_webhook_public_url: https://subdominio-temporal.ngrok.app/api/whatsapp/webhook
production_domain: null
```

- `application_url` identifica cómo se accede a Laravel dentro del entorno desplegado.
- `whatsapp_webhook_public_url` es la URL pública configurada en Meta. En Vagrant será la URL temporal de ngrok y podrá cambiar sin reprovisionar toda la aplicación.
- `production_domain` estará vacío hasta conocer el dominio real; en producción alimentará `application_hostname`, `APP_URL`, Apache y TLS.

Ngrok no reemplaza el TLS local: termina HTTPS público para Meta y reenvía al Apache de Vagrant. El túnel deberá configurar correctamente el host upstream y aceptar/confiar la CA local durante desarrollo.

## Principio de aislamiento tecnológico y selección por inventory

Las versiones elegidas son defaults iniciales independientes, no un perfil monolítico. La automatización se organizará con estas reglas:

1. cada tecnología vive en su propia carpeta con rol, defaults, templates, handlers, documentación y tests;
2. el inventory selecciona proveedor y versión de cada capacidad por separado;
3. una versión soportada se cambia solo en inventory;
4. una versión nueva se incorpora únicamente dentro de la carpeta de esa tecnología, agregando variables/tareas/templates específicos solo si hacen falta;
5. una implementación validada no se reescribe para simular compatibilidad con otra versión;
6. los playbooks generales orquestan capacidades (`operating_system`, `web_server`, `php_runtime`, `database`) sin conocer paquetes, servicios ni paths específicos;
7. cada rol valida localmente sus proveedores/versiones soportados y falla antes de cambiar el host;
8. solo se agregan validaciones cruzadas cuando hay una dependencia técnica comprobable, por ejemplo Apache con el socket expuesto por PHP-FPM; PostgreSQL no queda acoplado a PHP o Apache;
9. corregir un bug de una versión existente conserva tests de regresión y changelog; agregar compatibilidad crea archivos/casos nuevos dentro de esa tecnología.

Defaults iniciales recomendados:

```yaml
operating_system_family: debian
operating_system_version: "13"
web_server_provider: apache
web_server_version: "2.4"
php_provider: php
php_version: "8.4"
postgresql_version: "17"
```

Con PostgreSQL 18 ya soportado, migrar la selección será cambiar únicamente `postgresql_version: "18"` y seguir el runbook de upgrade de datos. Si todavía no existe soporte, se amplía `deploy/provisioning/roles/postgresql/` y sus tests, sin modificar los roles `common`, `apache` o `php`.

Como referencia local se revisó `/home/mgaray/desarrollo/elecciones/msa/deploy/provisioning/`: se reutilizarán su estructura reconocible de `ansible.cfg`, `group_vars`, inventories, playbooks por grupo, roles estándar, `Vagrantfile` y una interfaz `bin/deploy`. Allí `group_vars/all.yml` expone `pg_version`, lo cual es el patrón de selección deseado, pero `roles/pgdb/tasks/pg_install.yml` conserva paquetes `postgresql-10` hardcodeados. En este proyecto la variable y los nombres de paquetes/configuración deberán resolverse desde el mismo archivo de versión de PostgreSQL para que cambiar el inventory tenga efecto real y verificable.

No se copiarán del antecedente las contraseñas SSH en `ansible.cfg`, `host_key_checking=false`, paquetes en estado `latest`, claves dentro de roles, repositorios obsoletos, symlinks de escenarios ni scripts operativos extensos. La familiaridad se conserva en la organización y comandos, no en prácticas que hoy dificultarían seguridad e idempotencia.

## 1. Estado actual

### Arquitectura y ejecución local

La aplicación es Laravel 11 sobre PHP `^8.2`, con PostgreSQL y WhatsApp Cloud API. El entorno local se orquesta mediante [`docker-compose.yml`](../../docker-compose.yml):

| Servicio | Implementación actual | Persistencia/exposición | Clasificación |
|---|---|---|---|
| `app` | Imagen propia desde [`docker/app/Dockerfile`](../../docker/app/Dockerfile), PHP 8.3 CLI y `php artisan serve` | bind mount del repositorio; puerto `8000` | solo desarrollo |
| `db` | imagen oficial `postgres:16` | volumen `db_data`; puerto `5432` publicado al host | reutilizable como referencia de versión, no como configuración productiva |
| `composer` | misma imagen PHP con Composer 2 | bind mount del repositorio | herramienta de desarrollo |

El Dockerfile instala `pdo_pgsql`, `pgsql`, `intl`, `mbstring`, `xml` y `zip`, además de paquetes de compilación y utilidades. Usa un usuario no root alineado con UID/GID del host. No contiene Apache, PHP-FPM, health check de contenedor ni una imagen inmutable de aplicación.

### Laravel, dependencias y migraciones

- [`composer.json`](../../composer.json) exige PHP `^8.2`, Laravel 11, Filament 5 y Spatie Permission 6.25. La implementación local fija PHP 8.3; producción se planifica sobre PHP 8.4 nativo de Debian 13 y requiere validar Composer/tests antes del despliegue.
- Las extensiones comprobadas por el Dockerfile son la base de runtime. Antes de implementar el rol PHP se debe ejecutar `composer check-platform-reqs` sobre un release para confirmar requisitos transitivos y sumar, como mínimo operativo, `curl`, `ctype`, `fileinfo`, `filter`, `json`, `openssl`, `pdo`, `tokenizer` y las extensiones requeridas por Laravel/Filament disponibles en los paquetes base.
- Las migraciones se ejecutan actualmente con `make migrate`, que invoca `php artisan migrate` dentro del contenedor. Producción deberá usar `php artisan migrate --force` una sola vez desde el host de aplicación designado.
- El seeder [`database/seeders/BackofficeRolesAndPermissionsSeeder.php`](../../database/seeders/BackofficeRolesAndPermissionsSeeder.php) puede crear roles y un administrador local. No debe ejecutarse automáticamente en producción mientras pueda habilitar credenciales de desarrollo.

### Variables de entorno

La única plantilla versionada es [`.env.docker.example`](../../.env.docker.example), destinada a desarrollo. Incluye valores inseguros para producción (`APP_DEBUG=true`, contraseña PostgreSQL conocida, admin local habilitado y token de verificación de ejemplo). No debe copiarse sin transformación a un servidor.

Las variables consumidas se distribuyen principalmente en:

- [`config/app.php`](../../config/app.php): aplicación, entorno, debug, URL y clave;
- [`config/database.php`](../../config/database.php): PostgreSQL y `DB_SSLMODE`;
- [`config/logging.php`](../../config/logging.php): canales y nivel;
- [`config/medicina_laboral.php`](../../config/medicina_laboral.php): WhatsApp indirectamente, identificación, correo, timeouts y storage de adjuntos;
- [`config/backoffice.php`](../../config/backoffice.php): guard, roles y administrador local.

### PostgreSQL

El desarrollo usa PostgreSQL 16 con base `medicina`, usuario `postgres`, contraseña de ejemplo y volumen Docker. No existen en el repositorio configuraciones de cluster, `postgresql.conf`, `pg_hba.conf`, backups, restore, TLS de base ni política de retención.

### Web, HTTPS y WhatsApp

- No existe configuración de Apache ni Nginx.
- El servidor local es `artisan serve`; no es una opción productiva.
- [`routes/api.php`](../../routes/api.php) publica `GET` y `POST /api/whatsapp/webhook`.
- [`app/Http/Controllers/WhatsappWebhookController.php`](../../app/Http/Controllers/WhatsappWebhookController.php) valida el GET con `WHATSAPP_VERIFY_TOKEN` y procesa mensajes POST.
- [`app/Services/WhatsAppSender.php`](../../app/Services/WhatsAppSender.php) consume Graph API por HTTPS usando `WHATSAPP_TOKEN` y `WHATSAPP_PHONE_ID`.
- El README propone ngrok solo para pruebas locales. No existe dominio, DNS, TLS ni proxy productivo definido.

### Scheduler, colas y procesos

[`app/Console/Kernel.php`](../../app/Console/Kernel.php) programa `conversations:process-timeouts` cada minuto con `withoutOverlapping(10)`. [`Makefile`](../../Makefile) ofrece `make schedule-run` y comandos manuales de timeout. No existe cron del host.

`QUEUE_CONNECTION=sync` está en la plantilla local. No hay workers, Supervisor ni una cola productiva implementada. El uso del trait `Queueable` en un mailable no prueba que haya procesamiento asíncrono. No se deben instalar workers todavía.

### Logs, diagnóstico y health check

- Laravel soporta `single`, `daily`, `stderr`, syslog y otros canales en [`config/logging.php`](../../config/logging.php). Desarrollo usa `stderr`; la documentación también contempla `storage/logs/laravel.log`.
- [`app/Console/Commands/OperationalDoctor.php`](../../app/Console/Commands/OperationalDoctor.php) valida APP_KEY, conexión DB, logs, WhatsApp, timeouts y storage. Se ejecuta con `php artisan medicina:doctor` o `make doctor`.
- [`routes/health.php`](../../routes/health.php) expone `GET /up`, cubierto por [`tests/Feature/ApplicationHealthTest.php`](../../tests/Feature/ApplicationHealthTest.php). Solo verifica que Laravel responda; no comprueba dependencias.
- Existe un riesgo alto ya registrado como `LOG-001`: el webhook y el sender pueden registrar payloads, teléfonos y respuestas completas. La política de minimización y retención de datos sensibles sigue pendiente.

### Storage y persistencia

- La configuración proyecta discos y directorios bajo `medicina_laboral/drafts` y `medicina_laboral/anticipos`.
- Las implementaciones actuales [`MetadataDraftAttachmentStorage.php`](../../app/Services/Storage/MetadataDraftAttachmentStorage.php) y [`MetadataFinalAttachmentStorage.php`](../../app/Services/Storage/MetadataFinalAttachmentStorage.php) registran metadata y rutas lógicas, pero no descargan ni persisten el archivo real.
- La decisión del storage privado definitivo está pendiente (`BO-002` y [`docs/backoffice/storage-and-sensitive-files.md`](../../docs/backoffice/storage-and-sensitive-files.md)). No se puede considerar completa una estrategia de backup de adjuntos hasta resolverla.
- En una instalación host-based deberán persistir `.env`, `storage/app` y, según el canal, `storage/logs`; `storage/framework` es regenerable y no debe restaurarse ciegamente.

### Operación, scripts y CI/CD

- [`README.md`](../../README.md), [`docs/13-operacion-y-soporte.md`](../../docs/13-operacion-y-soporte.md) y [`Makefile`](../../Makefile) documentan setup, migraciones, tests, logs, scheduler y diagnóstico local.
- [`scripts/render_diagrams.sh`](../../scripts/render_diagrams.sh) solo renderiza documentación visual; no despliega.
- No existen pipelines de CI/CD, workflows de GitHub Actions, scripts de deploy, Ansible ni Vagrant.
- El antecedente `M8` de [`plan_dev/daily/2026-04-24.md`](../../plan_dev/daily/2026-04-24.md) proponía Ubuntu 24.04, Vagrant, hosts app/db y roles genéricos, pero quedó `pending`. Este documento lo reemplaza como diseño técnico vigente y cambia la preferencia a Debian, Apache y una estructura unificada bajo `deploy/`.

### Reutilizable frente a exclusivo de desarrollo

**Reutilizable:** PostgreSQL 16 como punto de partida; PHP 8.3 y extensiones comprobadas; comandos Artisan; migraciones; `/up`; `medicina:doctor`; scheduler Laravel; configuración por variables; separación lógica de storage; tests y documentación operativa.

**Exclusivo de desarrollo:** `artisan serve`; bind mount del repo; publicación de PostgreSQL en todas las interfaces del host Docker; credenciales de ejemplo; admin local; ngrok; `APP_DEBUG=true`; construcción por UID/GID; servicio Composer de Compose; `make` como envoltorio obligatorio.

## 2. Alcance que administrará Ansible

Ansible deberá administrar de forma idempotente:

1. compatibilidad y preparación de Debian/Ubuntu, timezone, locale, paquetes y actualizaciones controladas;
2. usuarios, grupos, SSH, sudo por `become` y estructura de directorios;
3. Apache, PHP-FPM, Composer y extensiones;
4. PostgreSQL local o remoto, reglas de acceso y backup;
5. releases Laravel, `.env`, shared storage, permisos, migraciones, optimización y health checks;
6. cron del scheduler y sus logs;
7. firewall, TLS, redirecciones y headers básicos;
8. logs, logrotate, backups y verificaciones de recuperación;
9. despliegue atómico cuando sea viable y rollback de código/configuración;
10. verificaciones operativas y documentación de runbooks.

No deberá administrar inicialmente DNS institucional, configuración dentro de Meta, infraestructura de red externa, monitoreo corporativo, CI/CD ni queues/workers. Esas integraciones se conectarán cuando existan contratos y responsables.

## 3. Arquitectura objetivo

### Topología A: aplicación y base separadas

```text
Internet / Meta
       |
     HTTPS 443
       |
Apache + PHP-FPM + Laravel (app_servers)
       |
PostgreSQL 5432 en red privada (db_servers)
```

Solo Apache expone 80/443. PostgreSQL acepta 5432 únicamente desde direcciones o redes de `app_servers` explícitamente autorizadas.

### Topología B: servidor único

```text
Internet / Meta
       |
     HTTPS 443
       |
Apache + PHP-FPM + Laravel + PostgreSQL
       (mismo host en app_servers y db_servers)
```

En esta topología Laravel debe usar `DB_HOST=127.0.0.1`; PostgreSQL puede limitarse a loopback y no necesita exponer 5432. El mismo host podrá pertenecer a ambos grupos sin duplicar tareas: cada rol será idempotente y los playbooks agruparán responsabilidades.

## 4. Estrategia de ejecución recomendada

Se recomienda **instalación directa en el host con Apache y PHP-FPM**, manteniendo Docker Compose exclusivamente para desarrollo.

| Criterio | Host + PHP-FPM | Contenedores administrados por Ansible |
|---|---|---|
| Operación inicial | menos capas y comandos estándar del SO | exige diseñar imágenes, registry, Compose productivo y persistencia |
| Apache requerido | integración nativa | proxy hacia contenedor y ciclo adicional |
| Consistencia local | menor | mayor, pero el Compose actual no es productivo |
| Backups | herramientas PostgreSQL y filesystem directas | requiere coordinar volúmenes/contenedores |
| Actualizaciones | apt + releases Laravel | imágenes inmutables, mejor a futuro pero aún inexistentes |
| Rollback | symlink de release; DB con límites | tag de imagen; DB conserva los mismos límites |
| Complejidad | adecuada para un MVP y equipo pequeño | mayor inversión y conocimiento operativo |

Esta recomendación debe revisarse si la institución ya opera una plataforma de contenedores, registry, observabilidad y backups estandarizados. No se reutilizará el Compose actual en producción sin un diseño nuevo.

## 5. Estructura propuesta de Ansible

```text
deploy/
├── README.md
├── docs/
│   ├── ansible-deployment-plan.md
│   └── runbooks/                    # futuro
└── provisioning/
    ├── README.md
    ├── ansible.cfg
    ├── requirements.yml
    ├── site.yml
    ├── app.yml
    ├── database.yml
    ├── deploy.yml
    ├── backup.yml
    ├── rollback.yml
    ├── inventories/
    │   ├── vagrant/hosts.yml
    │   ├── staging/hosts.yml
    │   └── production/hosts.yml
    ├── group_vars/
    │   ├── all.yml
    │   ├── app_servers.yml
    │   ├── db_servers.yml
    │   └── vault.yml                # cifrado
    ├── host_vars/                   # solo excepciones reales
    ├── roles/
    │   ├── common/
    │   │   ├── README.md
    │   │   └── vars/platforms/debian-13.yml
    │   ├── firewall/
    │   ├── apache/
    │   │   └── vars/versions/2.4.yml
    │   ├── php/
    │   │   └── vars/versions/8.4.yml
    │   ├── postgresql/
    │   │   ├── README.md
    │   │   ├── defaults/main.yml
    │   │   ├── tasks/main.yml
    │   │   ├── vars/versions/17.yml
    │   │   ├── templates/
    │   │   ├── handlers/main.yml
    │   │   └── tests/
    │   ├── laravel/
    │   ├── scheduler/
    │   ├── tls/
    │   ├── backup/
    │   └── monitoring/
    ├── bin/
    │   └── deploy                   # wrapper pequeño, futuro
    └── Vagrantfile
```

Esta estructura prioriza que alguien familiarizado con elecciones pueda ubicarse inmediatamente. Apache, PHP y PostgreSQL siguen separados porque tienen versiones y ciclos de vida distintos. Composer queda dentro de `laravel` mientras no requiera un rol propio.

Las carpetas mostradas son el diseño futuro, no artefactos creados en esta etapa. Durante `D1` se validará carga dinámica segura de variables/tareas por versión. La selección debe usar una lista explícita de versiones soportadas y `include_vars`/`include_tasks` con valores previamente validados, nunca paths arbitrarios provistos por el usuario.

### Alcance simple de la primera versión

La primera entrega funcional priorizará un recorrido entendible de punta a punta:

1. `common`: usuario, paquetes base, timezone y directorios;
2. `postgresql`: instalación 17, base/usuario y acceso app→DB;
3. `php` y `apache`: runtime y VirtualHost HTTP de prueba;
4. `laravel`: checkout por tag/SHA, Composer, `.env`, permisos, migraciones y `/up`;
5. `scheduler`: cron de Laravel;
6. `firewall`: puertos mínimos;
7. `backup`: dump local verificable;
8. `tls`: se activa cuando exista dominio.

Para mantenerla simple:

- `site.yml` importará `database.yml` y `app.yml`, como los playbooks compuestos del repo elecciones;
- `group_vars/all.yml` concentrará defaults y versiones; `app_servers.yml`/`db_servers.yml` solo tendrán variables de grupo;
- `ansible.cfg` apuntará al inventory elegido y a `roles/`, pero no guardará passwords;
- `bin/deploy` será, si se agrega, un wrapper corto para elegir inventory/ref y ejecutar `ansible-playbook`; la lógica seguirá en Ansible;
- Vagrant modelará una o dos VMs y llamará al mismo `site.yml`;
- los roles usarán módulos estándar y handlers; no se creará una colección, plugin propio, framework de perfiles ni repositorio externo de roles en esta etapa;
- el monitoreo inicial será `/up`, `medicina:doctor` y checks de servicios; no se instalará una plataforma de observabilidad;
- Molecule, CI/CD, bastion activo, copia externa de backups y soporte de versiones alternativas quedan para milestones posteriores.

La primera versión debe ser fácil de ejecutar manualmente y fácil de leer. La extensibilidad se concentra en variables y archivos por versión dentro de cada rol, sin construir abstracciones antes de necesitarlas.

### Selección independiente y soporte

Ejemplo conceptual del inventory, no ejecutable todavía:

```yaml
deployment_stack:
  operating_system:
    provider: debian
    version: "13"
  web_server:
    provider: apache
    version: "2.4"
  php_runtime:
    provider: php
    version: "8.4"
  database:
    provider: postgresql
    version: "17"
```

Los `pre_tasks` de `site.yml` y cada rol comprobarán que el proveedor y la versión existan. No habrá un framework de validación separado. Cada tecnología publicará su lista, por ejemplo `postgresql_supported_versions: ["17"]`. Agregar `"18"` y sus archivos/tests habilitará esa versión sin tocar selecciones de PHP, Apache o sistema operativo.

No habrá una matriz cartesiana obligatoria de toda la pila. Se mantendrá un registro breve de combinaciones probadas como evidencia, pero no bloqueará un cambio independiente salvo que exista una restricción declarada. Ejemplos:

- PostgreSQL 17→18: independiente de Apache/PHP; requiere validar driver `pdo_pgsql`, migración de datos y backup/restore.
- PHP 8.4→8.5: independiente de PostgreSQL server; requiere Composer, extensiones y suite Laravel.
- Apache: depende del contrato de socket/puerto de PHP-FPM, no de PostgreSQL.
- Debian 13→otro SO: puede cambiar nombres de paquetes/servicios de varias tecnologías; cada rol debe aportar archivos de plataforma compatibles o fallar explícitamente.

### Contrato de capacidades

| Capacidad | Responsabilidad | Variables neutrales | Implementación inicial | Validaciones contractuales |
|---|---|---|---|---|
| `operating_system` | base, locale, timezone, usuarios y repositorios | provider, version, timezone, locale, usuarios, update policy | Debian / 13 | familia/release exactos, usuario, reloj, idempotencia |
| `firewall` | puertos mínimos por grupo | puertos/cidrs autorizados | nftables | reglas efectivas y acceso SSH preservado |
| `web_server` | VirtualHost y proxy PHP | provider, version, dominio, docroot, límites, log paths | Apache / 2.4 | configtest, headers, estáticos y forwarding PHP |
| `php_runtime` | FPM, CLI y extensiones | provider, version, límites, extensiones, pool | PHP / 8.4 | versión, módulos, socket y FPM activo |
| `database` | cluster, base, rol y acceso | provider, version, DB, usuario, locale, redes, SSL | PostgreSQL / 17 | versión, conexión, `pg_hba`, ausencia de acceso público |
| `laravel` | releases, Composer, `.env`, migración y activación | repo/ref, paths, env, retención | releases por symlink | Composer, Artisan, permisos, `/up`, rollback de symlink |
| `scheduler` | ejecución periódica Laravel | usuario, comando, frecuencia, log | cron | entrada única y ejecución comprobada |
| `tls` | certificado, renovación y redirect | dominio, email, modo/cert paths | ACME | cadena, expiración, redirect y renovación |
| `backup` | DB y archivos persistentes | origen, destino, horario, retención | pg_dump + filesystem | backup reciente, checksum y restore probado |
| `monitoring` | checks básicos | endpoints, umbrales, alert target | scripts locales | servicios, `/up`, scheduler, backup, disco y TLS |

Cada tecnología debe incluir README, defaults, tasks, handlers solo cuando hagan falta, templates con `validate`, tests, versiones soportadas y changelog. Las variables obligatorias fallan temprano con `assert`. Ningún componente puede leer variables internas de otro: la integración usa outputs/contratos documentados, como `php_fpm_socket`.

## 6. Inventarios

Los inventarios versionados contendrán nombres y ejemplos seguros; direcciones sensibles y secretos se inyectarán por mecanismo acordado.

Cada inventory seleccionará proveedor y versión por capacidad. No declarará paquetes `apt`, sockets, nombres de servicio ni paths específicos de una distribución. Si una selección no está soportada o el sistema operativo detectado no coincide, el preflight se detendrá antes de realizar cambios.

### Hosts separados

```yaml
all:
  vars:
    operating_system_family: debian
    operating_system_version: "13"
    web_server_provider: apache
    web_server_version: "2.4"
    php_version: "8.4"
    postgresql_version: "17"
  children:
    app_servers:
      hosts:
        app-01:
          ansible_host: 192.0.2.10
    db_servers:
      hosts:
        db-01:
          ansible_host: 192.0.2.20
```

### Host único

```yaml
all:
  vars:
    operating_system_family: debian
    operating_system_version: "13"
    web_server_provider: apache
    web_server_version: "2.4"
    php_version: "8.4"
    postgresql_version: "17"
  children:
    app_servers:
      hosts:
        medicina-01:
          ansible_host: 192.0.2.30
    db_servers:
      hosts:
        medicina-01:
          ansible_host: 192.0.2.30
```

`vagrant` tendrá primero escenarios single/split para el perfil Debian 13. Ubuntu 24.04 se agregará únicamente con su perfil y sus componentes probados, no como una variante implícita. `staging` y `production` compartirán estructura, nunca valores secretos. La pertenencia duplicada de un host no debe duplicar instalación porque cada play aplica roles por grupo y Ansible consolida hosts.

El inventory contemplará acceso SSH directo como valor normal y bastion opcional, sin hardcodearlo:

```yaml
all:
  vars:
    ansible_user: deploy
    deployment_ssh_bastion_enabled: false
    deployment_ssh_bastion_host: null
```

Cuando se habilite bastion, `ansible_ssh_common_args` se derivará de variables validadas. Las claves privadas no se guardarán en el repositorio ni dentro de Vault salvo necesidad institucional explícita; se cargarán desde el agente SSH del operador.

Un **bastion** es un servidor intermedio al que Ansible entra primero para alcanzar máquinas que no aceptan SSH directo. No se usará en Vagrant. `deployment_ssh_bastion_enabled` quedará `false` y solo se implementará si la infraestructura real lo exige.

Para Vagrant se aplicará un bootstrap simple:

1. primera conexión con el usuario estándar `vagrant` y su clave administrada por Vagrant;
2. `bootstrap.yml` crea el usuario `deploy`, instala la clave pública autorizada y concede únicamente el sudo requerido por provisioning;
3. `site.yml` y despliegues posteriores conectan como `deploy`;
4. se valida `ansible all -m ping` y `sudo -n true` antes de continuar.

En servidores reales se pedirá un usuario inicial equivalente o que infraestructura entregue `deploy` ya creado. No se habilitará SSH por contraseña como camino normal.

## 7. Variables y secretos

### Clasificación

- **Comunes:** nombre de aplicación, paths, usuario/grupo, versiones compatibles, timezone, endpoint health, retención de releases.
- **Por entorno:** dominio, `APP_ENV`, `APP_URL`, log level, topología, hosts DB, TLS, backup schedule.
- **Por host:** IP privada, interfaz de firewall, socket/recursos especiales.
- **Secretos:** `APP_KEY`, DB password, tokens WhatsApp, credenciales SMTP, contraseña/bootstrap administrativo, claves o destinos de backup.

### Variables Laravel a contemplar

| Área | Variables | Recomendación productiva |
|---|---|---|
| App | `APP_NAME`, `APP_ENV`, `APP_DEBUG`, `APP_URL`, `APP_KEY` | `production`, `false`, HTTPS; clave estable desde Vault |
| DB | `DB_CONNECTION`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`, `DB_SSLMODE` | `pgsql`; host según topología; secreto; SSL según red/política |
| Logs | `LOG_CHANNEL`, `LOG_LEVEL` | `daily` o `stderr` según colector; `info` o superior; política PII previa |
| WhatsApp | `WHATSAPP_TOKEN`, `WHATSAPP_PHONE_ID`, `WHATSAPP_VERIFY_TOKEN` | todos secretos salvo identificador no sensible según política; rotables |
| Storage | `FILESYSTEM_DISK`, `MEDICINA_LABORAL_*STORAGE*` | no usar `metadata` cuando se habilite archivo real; storage privado |
| Correo | `MEDICINA_LABORAL_MAIL_*` y variables del mailer futuro | definir contrato SMTP antes de habilitar |
| Scheduler | `MEDICINA_LABORAL_SECOND_INACTIVITY_ACTION` | valor validado por doctor; cron externo cada minuto |
| Integración | `MAPUCHE_DRIVER`, `WORKER_IDENTIFICATION_DRIVER` y flags mock | no aceptar datos desconocidos ni usar mock en producción sin decisión explícita |
| Backoffice | `BACKOFFICE_GUARD`, `BACKOFFICE_LOCAL_ADMIN_ENABLED`, credenciales locales | admin local siempre deshabilitado; alta inicial por procedimiento seguro |
| Runtime | `CACHE_STORE`, `QUEUE_CONNECTION`, `SESSION_DRIVER` | file/sync son aceptables para host único inicial; revisar al escalar horizontalmente |

### Vault

La primera versión usará `deploy/provisioning/group_vars/vault.yml`, cifrado y con secretos nombrados por entorno cuando corresponda. Las variables no secretas los referencian con prefijo `vault_`. Inicialmente el controlador leerá la contraseña desde `~/.config/medicina-laboral/ansible-vault-password`. Ese archivo debe crearse manualmente con permisos `0600`, quedar fuera del repositorio y no sincronizarse sin un canal seguro. `ansible.cfg` no contendrá una ruta absoluta ligada a una persona: un wrapper o variable `ANSIBLE_VAULT_PASSWORD_FILE` apuntará al archivo. Si luego la cantidad de entornos vuelve incómodo un único vault, se separará sin cambiar los roles.

El password file ya fue creado el 2026-08-05. En D1 se creará `group_vars/vault.yml` con `ansible-vault create` o `encrypt`, inicialmente con valores de desarrollo existentes para WhatsApp, APP_KEY y base de datos. Ningún comando de validación deberá imprimirlos.

Ansible Vault protege los datos versionados en reposo, pero los secretos quedan descifrados durante la ejecución. Por eso las tareas sensibles usarán `no_log: true`, `.env` tendrá permisos `0600` y se documentará rekey/rotación. A futuro la contraseña podrá migrarse a un keyring o gestor institucional sin cambiar los archivos cifrados.

## 8. PostgreSQL

- Versión productiva acordada: **PostgreSQL 17**, incluida en Debian 13 y soportada oficialmente por PostgreSQL hasta noviembre de 2029. El desarrollo local seguirá temporalmente en PostgreSQL 16; antes de desplegar se ejecutará la suite contra 17 para validar compatibilidad.
- Crear cluster con UTF-8 y locale institucional confirmado; base y rol dedicados sin privilegios de superusuario.
- En host único: `listen_addresses = 'localhost'`, conexión por `127.0.0.1` o socket si Laravel lo valida.
- En hosts separados: escuchar solo en IP privada de DB. Generar entradas `pg_hba.conf` para la base/usuario y las IP/CIDR derivadas explícitamente de `app_servers`; usar `scram-sha-256`. Nunca `0.0.0.0/0` ni publicación a Internet.
- Firewall 5432 abierto únicamente desde app; validar desde un host autorizado y desde uno no autorizado.
- Definir si la conexión privada requiere TLS. `DB_SSLMODE=require` o superior solo después de desplegar certificados de DB; no declarar seguridad TLS inexistente.
- Migraciones se ejecutan desde un único app host mediante `run_once`, antes de activar el release cuando sean compatibles o durante ventana de mantenimiento cuando no lo sean.
- Backup inicial: `pg_dump --format=custom`, cuenta y `.pgpass` restringidas, retención y cifrado definidos. Restore con `pg_restore` hacia una base vacía de prueba y procedimiento documentado.

## 9. Apache y PHP

- VirtualHost con `DocumentRoot` en `/var/www/medicina-laboral/current/public`, `AllowOverride None` y reglas Laravel equivalentes a `public/.htaccess` mediante configuración explícita o `mod_rewrite` controlado.
- Apache sirve estáticos y envía PHP a PHP-FPM; no usar `mod_php`.
- Instalar **PHP 8.4** desde los repositorios de Debian 13 y módulos comprobados (`cli`, `fpm`, `pgsql`, `intl`, `mbstring`, `xml`, `zip`, `curl`, `opcache`) más requisitos confirmados por Composer. Laravel 11 exige PHP 8.2 o superior y PHP 8.4 conserva soporte de seguridad hasta diciembre de 2028. Antes de cerrar el rol se ejecutarán Composer y la suite completa sobre 8.4.
- Ajustar `upload_max_filesize`, `post_max_size`, `memory_limit`, `max_execution_time` y timeouts de Apache coherentes con el máximo actual de certificados (5 MiB por archivo, hasta 3), dejando margen para payload.
- Usuario web sin shell; el deploy user administra releases. Escritura solo en `shared/storage` y `shared/bootstrap-cache` enlazados, no en el código.
- Logs separados de acceso/error con logrotate. Aplicar minimización de URLs/identificadores cuando corresponda.
- HTTPS obligatorio, redirect 80→443, HSTS solo tras validar HTTPS estable, `X-Content-Type-Options`, política de framing compatible con Filament, referrer policy y una CSP probada antes de endurecer.
- Denegar `.env`, `.git`, backups, archivos ocultos y cualquier path fuera de `public/` por diseño del DocumentRoot.

## 10. HTTPS y webhook de WhatsApp

En producción la URL objetivo será `https://{{ production_domain }}/api/whatsapp/webhook`. En Vagrant, Meta utilizará `whatsapp_webhook_public_url` (ngrok), mientras que el acceso local será `application_url`. Dominio, aliases, email ACME, modo TLS y presencia de proxy serán variables por entorno. El playbook de TLS/webhook fallará temprano si se intenta habilitar un webhook productivo sin dominio. Se requiere:

1. dominio y DNS públicos apuntando al app/proxy;
2. entrada 443 (y 80 solo para redirect/ACME) en firewall institucional y local;
3. certificado válido mediante ACME/Let's Encrypt o PKI institucional;
4. renovación automática con prueba y reload de Apache;
5. configuración del callback y verify token en Meta;
6. salida HTTPS desde la app hacia Graph API;
7. health check separado en `/up`, sin secretos ni datos sensibles;
8. logs y alertas que no guarden payloads completos.

Debe verificarse si existe proxy/WAF institucional, terminación TLS previa, NAT o restricción de ACME. Ngrok queda excluido de producción. Restringir por IP a Meta no se recomienda como único control sin una fuente mantenible de rangos; el token de verificación protege el alta, pero la autenticidad de POST necesita una decisión específica sobre validación de firma de Meta, actualmente no implementada.

### TLS equivalente para Vagrant

Vagrant no puede obtener normalmente un certificado público para un dominio `.test`, pero sí puede reproducir la misma arquitectura:

- Apache escucha en 443 y redirige 80→443;
- `application_hostname=medicina-laboral.test` se resuelve hacia la IP de la VM app mediante `/etc/hosts` o mecanismo equivalente;
- una CA local de desarrollo emite el certificado del hostname;
- la clave privada de la CA queda fuera del repositorio, con permisos restrictivos;
- el certificado de la CA se instala como confiable en la máquina del desarrollador cuando se desee evitar advertencias del navegador;
- el mismo rol `tls` selecciona `tls_provider=local_ca` en Vagrant y `tls_provider=acme` o `provided` en producción;
- ngrok publica temporalmente el endpoint de Meta y reenvía a Apache HTTPS usando el host correcto.

Esto prueba VirtualHost, PHP-FPM, redirects, headers, permisos y webhook sobre HTTPS sin fingir que el certificado local es productivo.

## 11. Despliegue repetible de Laravel

Se recomienda estructura de releases desde el comienzo porque reduce estados parciales con un costo acotado:

```text
/var/www/medicina-laboral/
├── current -> releases/<release-id>
├── releases/
└── shared/
    ├── .env
    ├── storage/
    └── bootstrap-cache/
```

Flujo propuesto:

1. validar variables, conectividad, espacio y `deployment_release_ref` (tag o commit inmutable);
2. obtener código mediante artefacto o checkout limpio en un release nuevo;
3. verificar identidad del commit y ejecutar `composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction`;
4. enlazar `.env`, storage y cache compartidos; fijar ownership/permisos;
5. ejecutar `php artisan config:clear` antes de generar caches y pruebas mínimas;
6. ejecutar prechecks y, cuando corresponda, `php artisan migrate --force` con `run_once`;
7. ejecutar `php artisan optimize` y controles Artisan;
8. cambiar `current` de forma atómica;
9. recargar PHP-FPM, verificar `/up`, doctor y rutas críticas no mutantes;
10. conservar un número configurable de releases y limpiar solo después del éxito.

El **origen del release** es el lugar y la versión exacta desde donde Ansible obtiene el código que instala. Inicialmente será `git@github.com:martin-garay/unlu_medicina_laboral.git`, usando una deploy key SSH de solo lectura y un tag o SHA explícito; nunca se desplegará una rama mutable como `main` sin resolverla previamente a un commit. Cuando exista CI, la opción preferida será descargar un artefacto versionado y con checksum, evitando instalar desde Git en producción.

No usar `php artisan key:generate` en cada deploy: `APP_KEY` es un secreto estable. No ejecutar seeders automáticamente. El orden exacto de migración/activación exige que cada cambio de esquema sea backward-compatible o que se declare ventana de mantenimiento.

## 12. Scheduler y procesos de fondo

Crear `/etc/cron.d/medicina-laboral-scheduler` para el usuario de aplicación:

```text
* * * * * <app-user> cd /var/www/medicina-laboral/current && php artisan schedule:run >> /var/log/medicina-laboral/scheduler.log 2>&1
```

La ruta de PHP será explícita. Laravel ya evita solapamiento del comando de timeout; el cron solo debe existir una vez por entorno. Si en el futuro hay múltiples app hosts, definir un único `scheduler_host` o migrar a locks compartidos antes de habilitar cron en todos. Validar con `schedule:list`, ejecución manual y evidencia de eventos esperados.

No instalar Supervisor ni workers: la cola actual es `sync` y no hay jobs operativos desplegables. La estructura futura podrá agregar un rol independiente cuando exista una necesidad real.

## 13. Archivos persistentes

| Elemento | Persistente | Estrategia |
|---|---|---|
| `shared/.env` | sí | Vault → template `0600`; backup cifrado o recuperación desde secretos fuente |
| `shared/storage/app` | sí | filesystem privado; backup según RPO |
| adjuntos/certificados | futuro, sí | `/var/lib/medicina-laboral/private`; no activar hasta implementar driver real, acceso autorizado y auditoría |
| logs Laravel | según canal | rotación/retención; no restaurar normalmente |
| `storage/framework` | no como dato | directorios compartidos por permisos; caches/sesiones requieren decisión |
| `bootstrap/cache` | regenerable | shared writable o por release según estrategia probada |
| `public/storage` | solo si se usa | symlink controlado; nunca para certificados sensibles |
| backups | sí, fuera del árbol web | permisos restrictivos, cifrado y copia externa |
| certificados TLS | sí | administrados por ACME/PKI fuera del release |

Si sesiones/cache permanecen en archivos, un host único es compatible. Escalado horizontal requerirá backend compartido y no forma parte de la primera implementación.

**Storage privado** significa que los certificados y adjuntos no quedan dentro de `public/` ni pueden descargarse conociendo una URL. Para el MVP se recomienda un directorio local `/var/lib/medicina-laboral/private`, propiedad del usuario de aplicación, modo base `0750` y archivos `0640`. Laravel accederá mediante un disco `local` privado y cualquier descarga futura pasará por un controller que verifique permisos y registre auditoría. Hoy el código guarda solo metadata, por lo que esta ruta se preparará pero no se considerará operativa hasta implementar el driver real.

Para la primera versión se recomienda este storage local privado, no object storage, porque es más sencillo de operar y probar. En Vagrant se montará en un disco/directorio persistente separado del release; en producción será un filesystem dedicado o volumen del host app. Requisitos antes de guardar certificados reales:

- implementar el driver Laravel que descargue/persista el archivo, porque hoy solo existe metadata;
- checksum, nombre generado y validación MIME/tamaño;
- descarga exclusivamente mediante autorización del backoffice y auditoría;
- inclusión en backup cifrado y prueba de restore;
- no compartirlo mediante Apache ni `public/storage`.

Si en el futuro se necesita alta disponibilidad o múltiples app servers, se agregará un driver S3-compatible privado sin cambiar el contrato de aplicación.

## 14. Seguridad básica

- SSH por clave; usuario de deploy sin root permanente y `become` acotado.
- Root SSH y autenticación por contraseña deshabilitados cuando la política institucional lo permita y exista acceso de recuperación.
- Firewall deny-by-default; solo SSH desde redes administrativas y 80/443 públicos; DB privada.
- `APP_ENV=production`, `APP_DEBUG=false`, `.env` `0600`, secretos cifrados con Vault.
- Usuario PostgreSQL dedicado, SCRAM, mínimo privilegio y sin exposición global.
- Código propiedad del deploy user; FPM solo escribe rutas compartidas necesarias.
- Actualizaciones de seguridad controladas y reinicios planificados, no upgrades mayores automáticos.
- Admin local deshabilitado. No desplegar `admin@admin.com`/`admin123456`; crear administradores por procedimiento separado, auditable y con cambio/rotación inicial.
- HTTPS, headers, protección de archivos sensibles y backoffice bajo permisos existentes; evaluar controles institucionales adicionales sin romper el webhook.
- Resolver `LOG-001` antes de producción: minimizar payloads, teléfono, token y respuestas; definir retención y acceso.
- Rotación de APP/DB/WhatsApp/SMTP/backup con responsables. Rotar `APP_KEY` implica impacto sobre datos cifrados/cookies y requiere procedimiento especial.
- Verificar firma de POST de Meta (`X-Hub-Signature-256`) como hardening de aplicación; actualmente no existe y Ansible no puede suplirlo.

## 15. Backups y recuperación

Base inicial adoptada para poder implementar y ajustar después:

- dump PostgreSQL diario en formato custom, más backup previo a migraciones de riesgo;
- backup diario de storage privado cuando exista contenido real;
- retención sugerida 7 diarios, 4 semanales y 6 mensuales, ajustada a política institucional y sensibilidad;
- staging local en `/var/backups/medicina-laboral/{postgresql,files,manifests}` sobre el host DB para dumps y el host app para archivos;
- copia externa posterior obligatoria hacia storage/host institucional todavía pendiente; mantener solo la copia local no protege ante pérdida del servidor;
- cuenta y directorio exclusivos, `0700/0600`, sin exposición web;
- checks de tamaño, antigüedad, checksum y éxito; alerta por backup vencido;
- restore documentado y ensayado periódicamente en entorno aislado;
- registrar versión PostgreSQL, commit y fecha para correlacionar restore.

Se adopta provisionalmente **RPO de 24 horas** —podrían perderse hasta 24 horas de datos— y **RTO de 4 horas** —objetivo para restaurar el servicio—, con retención de 7 backups diarios, 4 semanales y 6 mensuales. Deben ser confirmados por negocio antes de producción.

Copiar archivos no equivale a tener recuperación. El criterio de aceptación será restaurar DB y storage en una VM limpia y completar health/doctor. La herramienta/destino externo y el responsable operativo siguen pendientes.

### Destino externo recomendado

La recomendación productiva es **restic sobre un storage S3-compatible institucional**, con bucket privado, credenciales exclusivas, cifrado propio de restic y política de retención. Es más resistente que copiar backups a otro directorio del mismo servidor y permite cambiar de proveedor sin cambiar el formato de backup.

Si no existe object storage institucional, la segunda opción es un servidor de backups separado accesible por SFTP/SSH con usuario restringido. No se recomienda NFS público ni una carpeta del mismo host como única copia.

Para Vagrant se simulará el destino externo con una carpeta del host fuera del disco de la VM, por ejemplo `deploy/.local/backups/`, ignorada por Git. Destruir y recrear la VM no debe borrar esa copia. Esto permite probar generación, retención y restore antes de disponer del destino productivo.

## 16. Observabilidad y diagnóstico

Post-deploy deberá verificar:

1. `apachectl configtest` y servicio activo;
2. PHP-FPM activo y socket accesible;
3. PostgreSQL activo y consulta desde Laravel;
4. HTTPS `/up` con status 200, hostname correcto y certificado vigente;
5. `php artisan medicina:doctor`, interpretando warnings según ambiente (hoy recomienda stderr específicamente para Docker y podría requerir ajuste futuro);
6. `php artisan schedule:list` y ejecución/control de cron;
7. log de deploy, Apache, FPM, Laravel, scheduler y PostgreSQL sin secretos;
8. espacio/inodos, expiración TLS y antigüedad/validez de backups.

`/up` es un liveness básico. No debe convertirse en un endpoint público con detalles de DB o secretos. Un readiness autenticado/local puede añadirse posteriormente si se define contrato.

### Política inicial de logs y monitoreo

Para producción se recomienda `LOG_CHANNEL=daily`, `LOG_LEVEL=info` y retención local de 14 días para Laravel, coordinada con `logrotate` para Apache, PHP-FPM y scheduler. La aplicación no deberá registrar:

- tokens, contraseñas, cookies, `.env` ni headers de autorización;
- payloads crudos completos de WhatsApp;
- cuerpos de certificados o adjuntos;
- números telefónicos completos ni datos médicos en mensajes de operación.

Cuando haga falta correlación se usarán `conversation_id`, `provider_message_id` truncado/hasheado, tipo de evento, resultado y duración. Los errores conservarán stack trace y código técnico, pero sus contextos se sanearán. El acceso a logs quedará restringido a operación/auditoría y la ampliación de retención necesitará aprobación por sensibilidad de los datos.

El monitoreo inicial no requiere una plataforma compleja. El rol `monitoring` expondrá checks ejecutables y retornos para:

- HTTPS `/up`, Apache, PHP-FPM y PostgreSQL;
- ejecución reciente del scheduler;
- antigüedad y validación del último backup;
- disco e inodos (warning 75 %, critical 90 %);
- certificado TLS (warning a 30 días, critical a 14 días);
- errores HTTP 5xx y fallos de envío a WhatsApp por encima de umbrales configurables.

Hasta elegir un sistema institucional, los fallos quedarán en syslog/journal y podrán notificar a un email configurable (`deployment_alert_email`). Ansible preparará la integración, pero no inventará un destinatario ni credenciales SMTP.

## 17. Rollback

- Fallo antes de activación: eliminar/conservar para diagnóstico el release incompleto sin tocar `current`.
- Fallo de configuración: no recargar servicios si las validaciones de template fallan; restaurar archivo respaldado por Ansible si el handler falla.
- Fallo de Composer: abortar antes de symlink y migración.
- Fallo de health después de activar: volver atómicamente al symlink anterior, recargar FPM y repetir health.
- Fallo de reinicio: mantener configuración previa cuando sea posible y reportar bloqueo; no encadenar reinicios.
- Fallo de migración: detener. No ejecutar automáticamente `migrate:rollback`; una migración puede ser irreversible o incompatible con el release anterior.

El rollback seguro exige migraciones expand/contract y compatibilidad hacia atrás. Para migraciones destructivas se requiere backup verificado, ventana de mantenimiento y runbook específico. El playbook `rollback.yml` revertirá código/config, no prometerá revertir datos.

## 18. Pruebas de infraestructura

Pipeline local mínimo por etapa:

- `yamllint`;
- `ansible-lint`;
- `ansible-playbook --syntax-check` por playbook/inventory;
- `ansible-playbook --check --diff` donde los módulos lo soporten, sin tratar check mode como prueba suficiente;
- convergencia real en Vagrant;
- segunda ejecución sin cambios inesperados (idempotencia);
- `apachectl configtest`, PHP modules, conexión DB, `/up`, doctor y scheduler;
- escenarios de una VM y dos VM;
- cada selección tecnológica en los escenarios declarados; los defaults iniciales cubren Debian 13 single/split y Ubuntu se prueba separadamente cuando se implemente.

Molecule puede incorporarse por roles complejos después de estabilizar la base. Vagrant aporta más valor inicial para validar systemd, Apache, PostgreSQL, firewall y topologías completas.

## 19. Vagrant

El `Vagrantfile` vivirá en `deploy/provisioning/` y solo modelará máquinas/redes. El provisioning invocará los mismos inventarios, roles y playbooks usados fuera de Vagrant.

Escenarios:

- `single-debian`: una VM Debian estable en ambos grupos;
- `split-debian`: app y DB en dos VM privadas;
- `single-ubuntu-2404`: variante de compatibilidad;
- opcionalmente `split-ubuntu-2404` cuando las etapas base pasen.

No se duplicarán scripts de instalación dentro del Vagrantfile. Deben parametrizarse RAM/CPU/IP y documentarse requisitos de provider. La disponibilidad local de Vagrant/VirtualBox/libvirt se verificará antes de implementar.

## 20. Etapas de implementación

Cada etapa será un milestone independiente, con actualización de `plan_dev/STATUS.md` y commit pequeño.

| Etapa | Objetivo y archivos esperados | Dependencias | Aceptación y pruebas | Riesgos/stop |
|---|---|---|---|---|
| D0 | aprobar decisiones bloqueantes; actualizar este documento | responsables institucionales | tabla de decisiones revisada | detener si no hay topología/acceso/dominio |
| D1 | estructura familiar: `provisioning/`, `ansible.cfg`, inventory Vagrant, `group_vars/all.yml`, `site.yml` y roles vacíos/documentados | D0 parcial | lint, syntax-check, `ansible-inventory --graph` y rechazo de versión inexistente | no introducir wrappers complejos ni paths arbitrarios |
| D2 | Vagrant Debian 13 single/split con versiones elegidas en inventory | D1 | VM accesible y mismas selecciones en ambas topologías | provider no disponible → blocked |
| D3 | rol `common` con Debian 13 inicial | D1-D2 | convergencia e idempotencia en Debian 13 | soporte Ubuntu se agrega dentro de `common` sin mezclar tareas |
| D4 | rol `postgresql` con versión 17 inicial, single/split | D3, secretos de prueba | conexión autorizada; rechazo externo; idempotencia | versiones nuevas agregan vars/tasks/tests solo aquí |
| D5 | roles separados `apache` y `php`, versiones desde inventory | D3 | configtest, FPM, contrato socket y página temporal | solo validar compatibilidad Apache/PHP realmente necesaria |
| D6 | rol `laravel`, releases y `.env` de prueba | D4-D5 | deploy repetible, Composer, permisos, `/up`, doctor | origen del artefacto/ref y storage |
| D7 | scheduler | D6 | cron único, `schedule:list`, ejecución observada | múltiples app hosts sin líder |
| D8 | TLS y webhook en staging | dominio/DNS, D6 | HTTPS, redirect, renovación dry-run, verificación Meta | proxy/NAT/ACME institucional |
| D9 | backup y restore DB/storage | destino/RPO/RTO, D4/D6 | restore completo en VM limpia | storage definitivo pendiente |
| D10 | firewall y hardening integrado | D4-D8, acceso recuperación | puertos mínimos y acceso preservado | lockout SSH; política institucional |
| D11 | monitoring/diagnóstico | D6-D9 | checks de servicios, TLS, disco, backups | plataforma de alertas sin definir |
| D12 | rollback de release | D6, migraciones compatibles | fallo inducido revierte symlink y health | rollback DB no automático |
| D13 | matriz final single/split de los defaults iniciales y runbooks | todas | lint, syntax, idempotencia, deploy y restore | no declarar producción sin prueba real |
| D14 | CI/CD opcional | decisión CI | validaciones automáticas sin secretos expuestos | runners/accesos sin definir |
| D15 | soporte Ubuntu 24.04, si se necesita | D1 y archivos de plataforma dentro de cada rol afectado | suite completa seleccionando Ubuntu desde inventory | no declarar compatibilidad por similitud; probar cada rol afectado |

## 21. Decisiones pendientes

| Decisión | Opciones | Recomendación | Impacto | Responsable sugerido | Estado |
|---|---|---|---|---|---|
| Runtime | host / contenedores | host + Apache/PHP-FPM | arquitectura completa | equipo técnico/operación | acordada para primera versión |
| SO/versiones | variables independientes | Debian 13 default; Ubuntu 24.04 seleccionable cuando su proveedor esté implementado | paquetes y tests | operación | arquitectura acordada |
| Topología inicial | uno / dos servidores | app y DB separados; misma IP válida para modo single-host | HA, seguridad, costo | infraestructura | acordada |
| Dominio y DNS | local/ngrok/productivo | variables separadas; `.test` local, ngrok para Meta, dominio real futuro | webhook/TLS | infraestructura/comunicaciones | desarrollo acordado; producción pendiente |
| Autoridad TLS | CA local / ACME / PKI institucional | CA local en Vagrant; estándar institucional o ACME en producción | renovación y red | seguridad/infraestructura | Vagrant acordado; producción pendiente |
| Proxy/WAF/NAT | directo / institucional | integrar estándar existente | vhost, firma, IPs | infraestructura | pendiente |
| SSH | bootstrap Vagrant/directo/bastion | Vagrant crea `deploy`; SSH directo, bastion deshabilitado | acceso Ansible | infraestructura/seguridad | Vagrant acordado; servidores reales pendientes |
| Secretos | Vault / gestor institucional | Vault; password file en home fuera de Git | operación y CI | seguridad/operación | password file creado; vault cifrado pendiente D1 |
| PostgreSQL | variable `postgresql_version` | 17 default; nueva versión amplía solo PostgreSQL y luego se selecciona en inventory | paquetes, backup, soporte | DBA/operación | arquitectura acordada, default sujeto a tests |
| PHP | variable `php_version` | 8.4 default; nueva versión amplía solo PHP y luego se selecciona en inventory | paquetes y soporte | operación/desarrollo | arquitectura acordada, default sujeto a tests |
| TLS PostgreSQL | requerido / red privada sin TLS | seguir política institucional; TLS en redes no confiables | certificados y config | DBA/seguridad | pendiente |
| Releases | symlink / in-place | symlink de releases | rollback y disco | desarrollo/operación | recomendada |
| Origen de release | git en host / artefacto CI | GitHub con deploy key read-only y tag/SHA; artefacto verificable a futuro | supply chain | desarrollo/operación | acordada para etapa inicial |
| Migraciones | deploy online / ventana | expand-contract; ventana para destructivas | disponibilidad/rollback | desarrollo/DBA | pendiente proceso |
| Storage privado | disco local / object storage / institucional | filesystem privado local para MVP; S3-compatible al escalar | adjuntos y backup | seguridad/negocio | infraestructura inicial acordada; driver real pendiente |
| Logs sensibles | filesystem / journal/colector | Laravel daily 14 días, nivel info, sin payload/PII; colector futuro | cumplimiento/diagnóstico | seguridad/negocio | política inicial definida; requiere cambio funcional LOG-001 |
| Backup destino | local + S3/SFTP | staging local; restic + S3-compatible recomendado, SFTP como alternativa | recuperación | operación/seguridad | simulación Vagrant definida; proveedor real pendiente |
| Retención/RPO/RTO | política institucional | 7 diarios/4 semanales/6 mensuales; RPO 24 h, RTO 4 h | costo y recuperación | negocio/operación | propuesta adoptada, confirmar con negocio |
| Admin inicial | seeder local / alta segura | procedimiento manual/auditable, local admin off | acceso backoffice | seguridad/administración | pendiente diseñar |
| Firma webhook | implementar / aceptar token GET | implementar validación de firma antes de producción | seguridad de entrada | desarrollo/seguridad | pendiente funcional fuera de Ansible |
| Rollback | código automático / DB manual | código automático, DB con runbook | riesgo de datos | desarrollo/DBA | recomendada |
| CI/CD | manual / GitHub Actions / plataforma institucional | diferir hasta D13; elegir plataforma institucional | automatización/secretos | DevOps | pendiente |
| Monitoreo | checks locales / plataforma institucional | checks locales, syslog y email configurable; integrar plataforma futura | alertas | operación | estrategia inicial definida; canal pendiente |

## Riesgos prioritarios

1. **Datos sensibles en logs:** payloads y teléfonos se registran actualmente; resolver antes de exponer producción.
2. **Archivos médicos no persistidos:** el storage es metadata-only; backup de adjuntos no puede cerrarse todavía.
3. **Webhook POST sin validación de firma comprobada:** TLS y verify token no cubren por sí solos autenticidad de cada POST.
4. **Credenciales de desarrollo:** la plantilla y documentación incluyen contraseña de admin local y DB; deben quedar deshabilitadas en producción.
5. **Rollback de migraciones:** ningún mecanismo de releases resuelve una migración destructiva.
6. **Infraestructura institucional desconocida:** dominio, proxy, firewall, PKI, SSH, backup y monitoreo pueden cambiar el diseño fino.
7. **Compatibilidad Debian/Ubuntu:** paquetes y nombres de servicio/repositorios deben probarse, no inferirse.
8. **Escalado:** sesiones/cache en archivo y scheduler único son adecuados solo para la topología inicial controlada.

## Criterio de aprobación de esta planificación

El plan ya permite comenzar `D1` (estructura base) y pruebas locales sin dominio real. La implementación sobre servidores reales no debe comenzar hasta disponer de IP/hostname y clave SSH; TLS/webhook requiere además dominio, DNS y autoridad de certificados. El storage de archivos médicos no se considerará productivo hasta implementar y auditar un driver real.

## Fuentes externas verificadas

- [Debian Releases](https://www.debian.org/releases/): Debian 13 `trixie` es la distribución estable vigente y Debian la recomienda para producción.
- [Anuncio oficial de Debian 13](https://www.debian.org/News/2025/20250809): Debian 13 incluye PHP 8.4, PostgreSQL 17 y Apache 2.4.
- [Versiones soportadas de PHP](https://www.php.net/supported-versions.php): PHP 8.4 tiene soporte de seguridad hasta el 31 de diciembre de 2028.
- [Deployment de Laravel 11](https://laravel.com/docs/11.x/deployment): Laravel 11 requiere PHP 8.2 o superior y escritura en `storage` y `bootstrap/cache`.
- [Política de versiones de PostgreSQL](https://www.postgresql.org/support/versioning/): PostgreSQL 17 recibe soporte hasta noviembre de 2029.
- [Documentación de Ansible Vault](https://docs.ansible.com/projects/ansible/latest/vault_guide/vault.html): Vault cifra datos en reposo y admite password file externo al repositorio.
