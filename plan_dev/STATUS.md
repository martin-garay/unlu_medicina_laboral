# Status

## Objetivo

Este archivo consolida el estado actual del proyecto y deja trazada la última ejecución relevante.

No debe reemplazar:
- el roadmap de `plan_dev/MASTER_PLAN.md`
- el detalle operativo de `plan_dev/daily/`
- el backlog de `plan_dev/BACKLOG.md`

---

## Fecha de última actualización
2026-08-27 20:33 -03

## Resumen ejecutivo
- Estado general del proyecto: el motor conversacional sigue en progreso y ya soporta menus interactivos por paso para selecciones acotadas de WhatsApp, manteniendo fallback por texto/numero.
- Último bloque completado: bootstrap y validacion remota del usuario operativo
  `deploy` en testing desde PC Uni.
- Milestone actual: `M4 - Deploy completo sin security/firewall` del daily
  2026-08-24 queda en `needs_review`: se corrigieron bloqueos de dry-run y
  compatibilidad OpenSSL en el rol `tls`, y se corrigio la precedencia de
  variables para que el inventory de testing no sea pisado por defaults. Se
  agrego `bin/check-deploy` para detectar esta clase de regresiones antes de
  commitear, con `ansible-lint` opcional en control nodes Python 3.8; falta
  reintentar `site.yml --check --diff` desde PC Uni.
- Próximo paso sugerido: desde PC Uni y `deploy/provisioning`, hacer `git pull`,
  usar el Ansible versionado del repo y ejecutar `ansible-playbook -i
  inventories/testing/hosts.yml site.yml --check --diff`; revisar que no toque
  SSH hardening, firewall ni nftables antes del apply real.
- Nota de seguridad operativa: `deploy/provisioning/group_vars/vault.yml` fue
  convertido a formato Ansible Vault; si los valores previos ya eran secretos
  reales, conviene rotarlos porque existian en commits anteriores.
- Nota de bootstrap SSH: el acceso inicial ahora usa el alias local
  `unlu-medicina-testing` y pisa explicitamente la key heredada para usar
  `~/.ssh/id_ed25519`, evitando que Ansible intente conectar como `mgaray` con
  la clave operativa `deploy_ed25519`.
- Nota de bastion: `ssh -o BatchMode=yes unlu-medicina-testing` confirmo que el
  corte `Connection closed by UNKNOWN port 65535` ocurre en el salto
  `martin@170.210.103.133` por password SSH interactiva. La password del bastion
  no resuelve el `ProxyJump` desde `ansible_password`; el prerequisito correcto
  es configurar clave SSH no interactiva en `unlu-pc`.
- Nota PC Uni: el control node de PC Uni reporto incompatibilidad con
  `ansible.builtin.raw` en `bootstrap-access.yml`; la tarea excepcional de
  bootstrap usa ahora `raw` corto con `skip_ansible_lint`.
- Nota bootstrap remoto: el control node de PC Uni logro ejecutar la primera
  tarea `raw`, pero los módulos Python remotos fallaron con
  `No module named 'ansible.module_utils.six.moves'`. El bootstrap posterior a
  los asserts usa ahora `raw` completo para no depender de módulos Python antes
  de crear y validar el usuario `deploy`.
- Nota PATH remoto: bajo `su`, el shell remoto no incluia `/usr/sbin` y fallaba
  con `groupadd: not found`. Las tareas `raw` de bootstrap fijan ahora un `PATH`
  administrativo explicito.
- Nota control node: se agregaron `requirements-control.txt`,
  `bin/setup-control-node` y wrappers `bin/ansible*` para crear y usar un venv
  limpio de Ansible bajo `deploy/.tools/`, evitando el Ansible global roto de PC
  Uni y la mezcla con paquetes `pip --user`. Desde PC Uni, `ansible -m ping`
  contra `app_servers` ya responde `pong` y la validacion de `sudo -n true`
  devuelve `rc=0`.
- Nota Python PC Uni: si el venv se crea con Python 3.8, puede aparecer
  `CryptographyDeprecationWarning`. No bloqueo la validacion remota, pero queda
  recomendado recrear el venv con Python 3.10 o superior cuando este disponible.

---

## Estado global

### Documentación
- estado: `in_progress`
- notas: la estructura operativa nueva ya tiene roles, precedencia y prompt lanzador estándar. La documentación de backoffice ya registra I1, I2 base, I3 base, especificación fina de módulos administrativos y convención de botón `Volver` en pantallas de detalle en `docs/backoffice/module-specs.md`. Quedaron creadas dailies separadas para módulos read-only, usuarios/roles, dashboard/reportes y storage/configuración.

### Motor de conversación
- estado: `in_progress`
- notas: el motor ya tiene una capa común de interacción (`ConversationInteractionService`), lookup/alta por canal y una entrada interna de prueba sin depender de WhatsApp. Desde M2 del daily 2026-06-26, `StepResult` puede devolver `menuConfig` y el motor emite menus interactivos especificos de paso ademas del menu principal. Desde M3, los mensajes entrantes duplicados con el mismo `provider_message_id` se ignoran antes de ejecutar handlers para evitar avances fantasma.

### Flujos
- aviso: `in_progress`
- anticipo: `in_progress`
- notas: la decisión vigente es tomar `AnticipoCertificado` como entidad actual de certificado y no crear una entidad nueva. Los pasos de sede, jornada laboral, tipo de ausentismo, domicilio circunstancial, tipo de certificado y adjuntar otro archivo usan menus interactivos en WhatsApp y conservan compatibilidad por texto/numero.

### Modelo de datos
- estado: `in_progress`
- notas: M5.1 implementó `avisos.estado` con default `inicial`, cambio de default de `anticipos_certificado.estado` a `inicial` y migración de anticipos existentes con estado `registrado`. M5.3 agregó la pivot `anticipo_certificado_aviso` para relación N a N, con backfill desde `anticipos_certificado.aviso_id` y compatibilidad temporal con la relación legacy.

### Testing
- estado: `in_progress`
- notas: última corrida completa `make test`: `209 passed`, `869 assertions`.

### Inactividad / scheduler
- estado: `in_progress`
- notas: el scheduler de timeouts existe y no fue modificado en este milestone.

### Logs / operación
- estado: `in_progress`
- notas: M6 relevó logs actuales y clasificó estructura objetivo en debug, operación, auditoría y métricas. La política de datos sensibles en logs queda como pendiente importante en `plan_dev/BACKLOG.md` (`LOG-001`), por decisión humana no se implementa todavía.

### Admin / roles / permisos
- estado: `base_done`
- notas: I1 instalo Filament `v5.6.1`, agrego panel base en `/admin` y auth minima con `App\Models\User`. I2 base quedo cerrado: Spatie Laravel Permission `6.25.0`, matriz en `config/backoffice.php`, seeder idempotente de roles/permisos/admin local y acceso al panel por `backoffice.access`. En desarrollo, `admin` debe sincronizar todos los permisos definidos; eso no saltea restricciones read-only de cada Resource. Desde P2 existe `ConversacionResource` read-only protegido por `conversaciones.view`. Desde P3 existe permiso `conversaciones.historial.view`, acción de ojo y pantalla de historial read-only. En la daily read-only P2/P3 se agrego `AvisoResource` con listado y detalle read-only. En P4/P5 se agrego `AnticipoCertificadoResource` con listado y detalle read-only, sin descarga, preview ni exposicion de `storage_path`. En P6 se agrego `AuditoriaAdministrativaResource` read-only. En la daily de usuarios P1 se agrego `UserResource` read-only. Desde 2026-05-04 todas las pantallas de detalle/historial abiertas desde listados tienen accion `Volver`.

### Auditoria administrativa
- estado: `base_done`
- notas: I3 base quedo cerrado. Existe contrato documental, tabla `auditoria_administrativa`, modelo `App\Models\AuditoriaAdministrativa`, servicio `App\Domain\Auditoria\Services\AuditoriaAdministrativaService`, tests y una integracion inicial en el seeder de roles/permisos con eventos `permissions.seeded` y `roles.seeded`. Desde P6 existe Resource read-only con listado, filtros y detalle.

### Integraciones futuras
- estado: `pending`
- notas: siguen planteadas como desacopladas y futuras.

### Deploy / Ansible
- estado: `in_progress`
- notas: Vagrant single/split funciona con VirtualBox 7.2.14 y Debian 13.1. Laravel está desplegado en ambas topologías con releases, datos compartidos, Vault, migraciones y health check HTTP 200. Desde 2026-08-19 el pase a testing se prepara primero sobre topología single-host, con usuario operativo `deploy`, bootstrap explícito y PostgreSQL local por `127.0.0.1`. Vagrant single ya cerró convergencia completa, idempotencia, monitoreo, backup y restore-test. Desde 2026-08-24 el rol `firewall` no ejecuta `flush ruleset` por defecto; sólo reemplaza su tabla administrada salvo que `firewall_flush_ruleset` se habilite explícitamente. Testing remoto queda configurado con `security_enabled: false` y `firewall_enabled: false` para permitir `site.yml` completo sin modificar hardening SSH ni firewall institucional.

---

## Última ejecución del agente

### Fecha/hora
- 2026-08-27 20:33 -03

### Plan diario usado
- `plan_dev/daily/2026-08-24.md`
- `plan_dev/daily/2026-08-27.md` no existe.

### Milestone trabajado
- `M4 - Deploy completo sin security/firewall`

### Resultado
- `needs_review`

### Resumen corto
- El primer `site.yml --check --diff` remoto llego hasta el rol `tls` y fallo en
  `Install TLS private key` con salida censurada por `no_log`.
- La causa probable es propia del modo check: las tareas locales de `openssl`
  no generaban archivos bajo `.local/tls`, pero `copy` necesitaba leer la clave
  privada local para calcular el cambio remoto.
- Se ajusto el rol `tls` para generar el material `local_ca` en la estacion de
  control incluso durante `--check` y para validar esos archivos antes de llegar
  a la tarea censurada.
- El reintento desde PC Uni avanzo hasta `Sign server certificate with local CA`
  y fallo porque `openssl x509` no reconoce `-copy_extensions`. Se reemplazo
  esa opcion por firma con archivo local de extensiones `-extfile`.
- El siguiente bloqueo ocurrio en `application : Checkout application release on
  control node`: PC Uni no llega a `github.com:22` y ademas el playbook estaba
  usando `workspace` por cargar `group_vars/all.yml` como `vars_files`, lo que
  pisaba el `application_release_id` del inventory.
- Se cambio testing a checkout Git por HTTPS y se retiro `group_vars/all.yml` de
  `vars_files` en los playbooks. `group_vars/all.yml` queda como group vars
  normal; `vars_files` se reserva para Vault.
- El reintento fallo en `Validate operating system selection` porque
  `supported_operating_systems` ya no estaba disponible dentro de
  `playbooks/validate.yml`. Se separo la matriz de soporte a
  `playbooks/vars/supported-platforms.yml` y se agrego `bin/check-deploy` para
  ejecutar esa validacion localmente antes de commitear.
- En PC Uni, `bin/check-deploy` avanzo hasta `ansible-lint` y fallo porque el
  venv usa Python 3.8; la version instalada de `ansible-lint` importa
  `functools.cache`, disponible recien desde Python 3.9. Se ajusto el check para
  saltear `ansible-lint` en Python < 3.10 y mantenerlo obligatorio en estaciones
  con Python moderno.

---

## Cambios realizados
- archivos tocados: `deploy/provisioning/roles/tls/tasks/main.yml`,
  `deploy/provisioning/roles/tls/defaults/main.yml`,
  `deploy/provisioning/roles/tls/README.md`,
  `deploy/provisioning/playbooks/vars/supported-platforms.yml`,
  `deploy/provisioning/bin/check-deploy`,
  `deploy/provisioning/bin/setup-control-node`,
  `deploy/provisioning/group_vars/all.yml`,
  `deploy/provisioning/playbooks/validate.yml`,
  `deploy/provisioning/requirements-control.txt`,
  `deploy/provisioning/inventories/testing/hosts.yml`,
  `deploy/provisioning/playbooks/*.yml`, `deploy/docs/deployment-guide.md`,
  `deploy/provisioning/inventories/README.md`,
  `deploy/README.md`, `plan_dev/daily/2026-08-24.md` y `plan_dev/STATUS.md`.
- resumen tecnico: las tareas que crean directorio, CA, key, CSR y certificado
  local para `tls_provider: local_ca` usan `check_mode: false`, porque son
  insumo local para simular las copias remotas. Se agrego una validacion `stat`
  y `assert` sobre esos archivos para reportar errores de `openssl` o paths
  antes de la copia de clave privada con `no_log`. La firma del certificado de
  servidor ahora usa un archivo `*.ext` con `subjectAltName`, `basicConstraints`,
  `keyUsage` y `extendedKeyUsage`, evitando depender de
  `openssl x509 -copy_extensions`. El inventory de testing ahora usa
  `https://github.com/martin-garay/unlu_medicina_laboral.git` para evitar el
  puerto SSH 22 bloqueado desde PC Uni. Los playbooks ya no cargan
  `group_vars/all.yml` como `vars_files` para evitar que defaults de play pisen
  variables del inventory; mantienen `../group_vars/vault.yml` cuando requieren
  secretos. La matriz `supported_*` usada por `validate.yml` se movio a
  `playbooks/vars/supported-platforms.yml`, porque no es un default de entorno.
  `bin/check-deploy` valida lint, syntax-checks, invariantes de testing y la
  ausencia de `../group_vars/all.yml` en `vars_files`. En Python 3.8, el check
  saltea `ansible-lint` para no bloquear el deploy por incompatibilidad de la
  herramienta; el lint completo se mantiene en estaciones con Python 3.10+.
- documentación actualizada: si; la guia de deploy y el README de inventarios
  explican la regla de precedencia, el uso de HTTPS para testing y el check
  pre-commit.
- runtime Laravel/Docker modificado: no; sólo provisioning y documentación.
- diagramas actualizados: no; no cambió arquitectura runtime Laravel, flujos ni modelo de datos.

---

## Validaciones

### Automáticas
- tests de Laravel: no corresponden; no hubo cambios funcionales.
- checks:
  - `bin/yamllint roles/tls/defaults/main.yml roles/tls/tasks/main.yml inventories/testing/hosts.yml playbooks/bootstrap-access.yml requirements-control.txt`
  - `bin/yamllint group_vars/all.yml inventories playbooks roles/tls/defaults/main.yml roles/tls/tasks/main.yml requirements-control.txt`
  - `bin/ansible-inventory -i inventories/testing/hosts.yml --host avisos-testing`
  - `bin/ansible -i inventories/testing/hosts.yml avisos-testing -m debug -a 'var=application_release_id'`
  - `bin/ansible -i inventories/testing/hosts.yml avisos-testing -m debug -a 'var=application_git_repo'`
  - `bin/ansible -i inventories/testing/hosts.yml avisos-testing -m debug -a 'msg="security={{ security_enabled }} firewall={{ firewall_enabled }} ref={{ application_git_ref }}"'`
  - `bin/ansible-playbook -i inventories/testing/hosts.yml playbooks/bootstrap-access.yml --syntax-check`
  - `bin/ansible-playbook -i inventories/testing/hosts.yml playbooks/application.yml --syntax-check`
  - `bin/ansible-playbook -i inventories/testing/hosts.yml playbooks/security.yml --syntax-check`
  - `bin/ansible-playbook -i inventories/testing/hosts.yml playbooks/validate.yml --check`
  - `bin/ansible-playbook -i inventories/testing/hosts.yml site.yml --syntax-check`
  - syntax-check por lote de `playbooks/*.yml` contra inventory testing
  - `bin/ansible-playbook -i inventories/vagrant/single/hosts.yml site.yml --syntax-check`
  - `bin/ansible-playbook -i inventories/vagrant/split/hosts.yml site.yml --syntax-check`
  - `bin/ansible-lint roles/tls/tasks/main.yml`
  - `bin/ansible-lint playbooks/application.yml`
  - `bin/ansible-lint playbooks/bootstrap-access.yml`
  - `bin/ansible-lint site.yml`
  - `bin/check-deploy`
  - `git diff --check`
- resultado: sin errores.
- observación: `ansible-lint` emite un warning de entorno por `PATH` del venv
  local, sin violaciones. En PC Uni, `bin/check-deploy` saltea `ansible-lint`
  si el venv se creo con Python 3.8.

### Manuales sugeridas
- validar `ssh unlu-medicina-testing-deploy 'whoami; sudo -n true; python3 --version'`.
- reintentar desde PC Uni: `ansible-playbook -i inventories/testing/hosts.yml
  site.yml --check --diff`.
- antes de commitear cambios futuros de provisioning, correr
  `cd deploy/provisioning && bin/check-deploy`.

---

## Bloqueos actuales
- mantener `security_enabled: false` y `firewall_enabled: false` en testing hasta
  decidir cómo preservar los accesos y servicios institucionales existentes.

---

## Decisiones humanas pendientes
- informar dominio/DNS, PKI/ACME y proxy/WAF cuando estén disponibles.
- informar IP/hostname, usuario/clave SSH y bastion si aplica antes de operar servidores reales.
- elegir canal real de alertas; una segunda copia de backups en otro servidor institucional queda fuera del alcance actual.
- confirmar con negocio retención, RPO/RTO y política inicial de logs sensibles.
- implementar validación de firma de los POST del webhook antes de producción.
- confirmar si avisos `observado` deben poder recibir anticipo o si pasan a ser bloqueantes
- confirmar si futuras asociaciones adicionales de avisos serán manuales, automáticas o mixtas
- confirmar si `anticipos_certificado.aviso_id` quedará como cache del primer aviso o se eliminará luego de migrar lecturas a pivot
- confirmar estrategia de storage privado para certificados médicos

---

## Próximo milestone recomendado
- reintentar `M4 - Deploy completo sin security/firewall` desde PC Uni con el
  commit actualizado.

---

## Referencia breve a backlog
- `LOG-001`: definir política de datos sensibles en logs quedó registrado como pendiente importante.
- `BO-001`: definido y marcado `done`; matriz en `docs/backoffice/permissions.md`.
- `BO-002`: definir estrategia de storage privado de certificados.
- `BO-003`: definir operación manual de asociaciones aviso-certificado.
- `BO-004`: definir futuro de `anticipos_certificado.aviso_id`.
