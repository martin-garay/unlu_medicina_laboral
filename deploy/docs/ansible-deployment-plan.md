# Plan de despliegue con Ansible

## Propósito y estado

Este documento es la fuente de verdad técnica para planificar el despliegue de Medicina Laboral UNLu con Ansible. Está basado en el repositorio relevado el 2026-08-04 y separa explícitamente:

- **actual**: comportamiento comprobado en archivos existentes;
- **recomendado**: diseño propuesto para implementar en etapas posteriores;
- **pendiente**: decisión o dependencia que requiere confirmación.

Esta etapa no implementa Ansible, Vagrant ni configuración productiva. El entorno Docker local permanece sin cambios.

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

- [`composer.json`](../../composer.json) exige PHP `^8.2`, Laravel 11, Filament 5 y Spatie Permission 6.25. La implementación local fija PHP 8.3; esa es la versión inicial recomendada para producción, sujeta a disponibilidad en Debian.
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
├── ansible.cfg
├── requirements.yml
├── inventories/
│   ├── vagrant/
│   │   ├── hosts.yml
│   │   ├── group_vars/
│   │   └── host_vars/
│   ├── staging/
│   └── production/
├── playbooks/
│   ├── site.yml
│   ├── app.yml
│   ├── database.yml
│   ├── deploy.yml
│   ├── backup.yml
│   └── rollback.yml
├── roles/
│   ├── common/
│   ├── firewall/
│   ├── apache_php/
│   ├── postgresql/
│   ├── application/
│   ├── laravel_scheduler/
│   ├── tls/
│   ├── backup/
│   └── monitoring/
└── vagrant/
    ├── Vagrantfile
    └── README.md
```

`apache_php` se mantiene unido inicialmente porque sus versiones, socket FPM, VirtualHost y límites de subida están fuertemente coordinados. `composer` no justifica un rol propio. `monitoring` será pequeño y solo cubrirá checks básicos hasta elegir una plataforma.

### Contrato de roles

| Rol | Responsabilidad | Variables principales | Dependencias/handlers | Templates, archivos y validaciones |
|---|---|---|---|---|
| `common` | paquetes base, locale/timezone, usuarios, directorios | usuarios, timezone, locale, paquetes, política de updates | handler opcional de daemon reload | sudoers acotado; validar usuario, grupos, reloj y paquetes |
| `firewall` | política deny-by-default y puertos por grupo | SSH, HTTP/HTTPS, redes DB permitidas | reload de firewall | reglas nftables/ufw según decisión; comprobar puertos efectivos |
| `apache_php` | Apache, PHP-FPM, extensiones y VirtualHost | dominio, PHP, socket, límites, timeouts, log paths | reload/restart Apache y PHP-FPM | vhost, pool/ini; `apachectl configtest`, `php -m`, estado FPM |
| `postgresql` | cluster, base, rol, acceso y tuning mínimo | versión, DB, usuario, locale, listen, CIDR autorizados, SSL | restart/reload PostgreSQL | `postgresql.conf`, `pg_hba.conf`; conexión local/remota y ausencia de acceso público |
| `application` | releases, Composer, `.env`, shared dirs, migración y activación | repo/artefacto, ref, paths, env no secreto/secreto, retención releases | reload PHP-FPM tras activar | `.env`, symlinks; Composer, Artisan, permisos, `/up`, doctor |
| `laravel_scheduler` | cron de `schedule:run` | usuario, path `current`, frecuencia, log | no requiere restart | `/etc/cron.d/...`; `cron` válido y ejecución manual controlada |
| `tls` | certificado, renovación y redirect HTTPS | dominio, email ACME, método/cert paths | reload Apache | vhost TLS/renovación; cadena, expiración, redirect y HTTPS |
| `backup` | dumps DB y archivos persistentes, retención | destino, horario, cifrado, retención, credenciales | timer/cron reload cuando aplique | scripts/config fuera del release; backup verificable y restore documentado |
| `monitoring` | health checks locales y estado básico | URL, umbrales disco/certificado, endpoints | ninguno inicialmente | scripts/checks; Apache, FPM, DB, `/up`, scheduler, backup, disco y TLS |

Cada rol debe incluir `defaults/main.yml`, `tasks/main.yml`, handlers solo cuando hagan falta, templates con `validate` cuando sea posible y un README corto. Las variables obligatorias deben fallar temprano con `assert`.

## 6. Inventarios

Los inventarios versionados contendrán nombres y ejemplos seguros; direcciones sensibles y secretos se inyectarán por mecanismo acordado.

### Hosts separados

```yaml
all:
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

`vagrant` tendrá Debian estable como escenario principal y Ubuntu 24.04 como variante. `staging` y `production` compartirán estructura, nunca valores secretos. La pertenencia duplicada de un host no debe duplicar instalación porque cada play aplica roles por grupo y Ansible consolida hosts.

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

Recomendación: un archivo cifrado por entorno, por ejemplo `inventories/production/group_vars/all/vault.yml`, con nombres prefijados `vault_`; las variables no secretas los referencian. La contraseña de Vault nunca se versiona: se obtiene interactivamente o desde un gestor/CI autorizado. Usar `no_log: true` en tareas que rendericen o manipulen secretos, permisos `0600` para `.env` y un procedimiento de rekey/rotación. No generar secretos en esta etapa.

## 8. PostgreSQL

- Versión inicial recomendada: **16**, alineada con Docker y con soporte disponible; confirmar paquetes oficiales de cada Debian/Ubuntu antes de fijarla.
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
- Instalar PHP 8.3 y módulos comprobados (`cli`, `fpm`, `pgsql`, `intl`, `mbstring`, `xml`, `zip`, `curl`, `opcache`) más requisitos confirmados por Composer. Debian puede requerir repositorio externo: decisión pendiente de supply chain.
- Ajustar `upload_max_filesize`, `post_max_size`, `memory_limit`, `max_execution_time` y timeouts de Apache coherentes con el máximo actual de certificados (5 MiB por archivo, hasta 3), dejando margen para payload.
- Usuario web sin shell; el deploy user administra releases. Escritura solo en `shared/storage` y `shared/bootstrap-cache` enlazados, no en el código.
- Logs separados de acceso/error con logrotate. Aplicar minimización de URLs/identificadores cuando corresponda.
- HTTPS obligatorio, redirect 80→443, HSTS solo tras validar HTTPS estable, `X-Content-Type-Options`, política de framing compatible con Filament, referrer policy y una CSP probada antes de endurecer.
- Denegar `.env`, `.git`, backups, archivos ocultos y cualquier path fuera de `public/` por diseño del DocumentRoot.

## 10. HTTPS y webhook de WhatsApp

La URL objetivo será `https://<dominio>/api/whatsapp/webhook`. Se requiere:

1. dominio y DNS públicos apuntando al app/proxy;
2. entrada 443 (y 80 solo para redirect/ACME) en firewall institucional y local;
3. certificado válido mediante ACME/Let's Encrypt o PKI institucional;
4. renovación automática con prueba y reload de Apache;
5. configuración del callback y verify token en Meta;
6. salida HTTPS desde la app hacia Graph API;
7. health check separado en `/up`, sin secretos ni datos sensibles;
8. logs y alertas que no guarden payloads completos.

Debe verificarse si existe proxy/WAF institucional, terminación TLS previa, NAT o restricción de ACME. Ngrok queda excluido de producción. Restringir por IP a Meta no se recomienda como único control sin una fuente mantenible de rangos; el token de verificación protege el alta, pero la autenticidad de POST necesita una decisión específica sobre validación de firma de Meta, actualmente no implementada.

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

1. validar variables, conectividad, espacio y ref (tag/commit inmutable);
2. obtener código mediante artefacto o checkout limpio en un release nuevo;
3. verificar identidad del commit y ejecutar `composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction`;
4. enlazar `.env`, storage y cache compartidos; fijar ownership/permisos;
5. ejecutar `php artisan config:clear` antes de generar caches y pruebas mínimas;
6. ejecutar prechecks y, cuando corresponda, `php artisan migrate --force` con `run_once`;
7. ejecutar `php artisan optimize` y controles Artisan;
8. cambiar `current` de forma atómica;
9. recargar PHP-FPM, verificar `/up`, doctor y rutas críticas no mutantes;
10. conservar un número configurable de releases y limpiar solo después del éxito.

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
| adjuntos/certificados | futuro, sí | no activar hasta definir driver; cifrado, acceso auditado y backup |
| logs Laravel | según canal | rotación/retención; no restaurar normalmente |
| `storage/framework` | no como dato | directorios compartidos por permisos; caches/sesiones requieren decisión |
| `bootstrap/cache` | regenerable | shared writable o por release según estrategia probada |
| `public/storage` | solo si se usa | symlink controlado; nunca para certificados sensibles |
| backups | sí, fuera del árbol web | permisos restrictivos, cifrado y copia externa |
| certificados TLS | sí | administrados por ACME/PKI fuera del release |

Si sesiones/cache permanecen en archivos, un host único es compatible. Escalado horizontal requerirá backend compartido y no forma parte de la primera implementación.

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

Definir RPO/RTO antes de implementar. Base propuesta sujeta a aprobación:

- dump PostgreSQL diario en formato custom, más backup previo a migraciones de riesgo;
- backup diario de storage privado cuando exista contenido real;
- retención sugerida 7 diarios, 4 semanales y 6 mensuales, ajustada a política institucional y sensibilidad;
- destino fuera del servidor principal; cifrado en tránsito y reposo;
- cuenta y directorio exclusivos, `0700/0600`, sin exposición web;
- checks de tamaño, antigüedad, checksum y éxito; alerta por backup vencido;
- restore documentado y ensayado periódicamente en entorno aislado;
- registrar versión PostgreSQL, commit y fecha para correlacionar restore.

Copiar archivos no equivale a tener recuperación. El criterio de aceptación será restaurar DB y storage en una VM limpia y completar health/doctor. La ubicación, herramienta, retención y responsable siguen pendientes.

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
- Debian principal y Ubuntu 24.04 como matriz de compatibilidad.

Molecule puede incorporarse por roles complejos después de estabilizar la base. Vagrant aporta más valor inicial para validar systemd, Apache, PostgreSQL, firewall y topologías completas.

## 19. Vagrant

Vagrant vivirá en `deploy/vagrant/` y solo modelará máquinas/redes. El provisioning invocará los mismos inventarios, roles y playbooks usados fuera de Vagrant.

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
| D1 | base: `ansible.cfg`, requirements, inventarios de ejemplo, lint config | D0 parcial | lint, syntax-check de playbook vacío seguro | colecciones/versiones sin soporte |
| D2 | Vagrant Debian 1 VM y Ubuntu variante | D1 | VM accesible y mismo inventory | provider no disponible → blocked |
| D3 | rol `common` | D1-D2 | convergencia e idempotencia en ambos SO | política SSH/updates ambigua |
| D4 | rol `postgresql`, escenario single y split | D3, secretos de prueba | conexión autorizada; rechazo externo; idempotencia | locale/red/versión sin definir |
| D5 | `apache_php` sin aplicación real | D3 | configtest, FPM y página de prueba temporal | repositorio PHP/supply chain |
| D6 | rol `application`, releases y `.env` de prueba | D4-D5 | deploy repetible, Composer, permisos, `/up`, doctor | origen del artefacto/ref y storage |
| D7 | scheduler | D6 | cron único, `schedule:list`, ejecución observada | múltiples app hosts sin líder |
| D8 | TLS y webhook en staging | dominio/DNS, D6 | HTTPS, redirect, renovación dry-run, verificación Meta | proxy/NAT/ACME institucional |
| D9 | backup y restore DB/storage | destino/RPO/RTO, D4/D6 | restore completo en VM limpia | storage definitivo pendiente |
| D10 | firewall y hardening integrado | D4-D8, acceso recuperación | puertos mínimos y acceso preservado | lockout SSH; política institucional |
| D11 | monitoring/diagnóstico | D6-D9 | checks de servicios, TLS, disco, backups | plataforma de alertas sin definir |
| D12 | rollback de release | D6, migraciones compatibles | fallo inducido revierte symlink y health | rollback DB no automático |
| D13 | matriz final 1/2 hosts, Debian/Ubuntu, runbooks | todas | lint, syntax, idempotencia, deploy y restore | no declarar producción sin prueba real |
| D14 | CI/CD opcional | decisión CI | validaciones automáticas sin secretos expuestos | runners/accesos sin definir |

## 21. Decisiones pendientes

| Decisión | Opciones | Recomendación | Impacto | Responsable sugerido | Estado |
|---|---|---|---|---|---|
| Runtime | host / contenedores | host + Apache/PHP-FPM | arquitectura completa | equipo técnico/operación | recomendada, pendiente aprobar |
| SO/versiones | Debian estable / Ubuntu 24.04 | Debian principal, Ubuntu compatible | paquetes y tests | operación | contexto dado, versión Debian pendiente |
| Topología inicial | uno / dos servidores | dos si hay red/operación; uno para MVP acotado | HA, seguridad, costo | infraestructura | pendiente |
| Dominio y DNS | institucional / nuevo | subdominio institucional | webhook/TLS | infraestructura/comunicaciones | pendiente |
| Autoridad TLS | ACME / PKI institucional / proxy | usar estándar institucional; ACME si no existe | renovación y red | seguridad/infraestructura | pendiente |
| Proxy/WAF/NAT | directo / institucional | integrar estándar existente | vhost, firma, IPs | infraestructura | pendiente |
| SSH | bastion/directo, usuario, claves | clave + usuario deploy + bastion si existe | acceso Ansible | infraestructura/seguridad | pendiente |
| Secretos | Vault / gestor institucional | Vault inicialmente; integrar gestor si existe | operación y CI | seguridad/operación | pendiente |
| PostgreSQL | 16 / versión institucional | 16 por alineación local | paquetes, backup, soporte | DBA/operación | pendiente confirmar |
| PHP | 8.3 / versión disponible compatible | 8.3 | repositorio y soporte | operación/desarrollo | pendiente confirmar |
| TLS PostgreSQL | requerido / red privada sin TLS | seguir política institucional; TLS en redes no confiables | certificados y config | DBA/seguridad | pendiente |
| Releases | symlink / in-place | symlink de releases | rollback y disco | desarrollo/operación | recomendada |
| Origen de release | git en host / artefacto CI | artefacto verificable a futuro; git por tag en fase inicial | supply chain | desarrollo/operación | pendiente |
| Migraciones | deploy online / ventana | expand-contract; ventana para destructivas | disponibilidad/rollback | desarrollo/DBA | pendiente proceso |
| Storage privado | disco local / object storage / institucional | storage privado institucional si existe | adjuntos y backup | seguridad/negocio | bloquea archivos reales |
| Logs sensibles | filesystem / journal/colector | colector institucional o daily con política PII | cumplimiento/diagnóstico | seguridad/negocio | bloqueada por LOG-001 |
| Backup destino | segundo host / object storage / plataforma | fuera del servidor y cifrado | recuperación | operación/seguridad | pendiente |
| Retención/RPO/RTO | política institucional | definir antes de rol backup | costo y recuperación | negocio/operación | pendiente |
| Admin inicial | seeder local / alta segura | procedimiento manual/auditable, local admin off | acceso backoffice | seguridad/administración | pendiente diseñar |
| Firma webhook | implementar / aceptar token GET | implementar validación de firma antes de producción | seguridad de entrada | desarrollo/seguridad | pendiente funcional fuera de Ansible |
| Rollback | código automático / DB manual | código automático, DB con runbook | riesgo de datos | desarrollo/DBA | recomendada |
| CI/CD | manual / GitHub Actions / plataforma institucional | diferir hasta D13; elegir plataforma institucional | automatización/secretos | DevOps | pendiente |
| Monitoreo | checks locales / plataforma institucional | checks locales + integración existente | alertas | operación | pendiente |

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

El plan está listo para revisión cuando el equipo confirma la ubicación `deploy/`, la recomendación host-based y la tabla de decisiones. La implementación no debe comenzar por roles de aplicación hasta resolver al menos topología inicial, acceso SSH, dominio/TLS, versiones, secretos y estrategia de storage/backup aplicable al alcance.

