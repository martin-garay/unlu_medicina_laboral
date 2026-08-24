# Status

## Objetivo

Este archivo consolida el estado actual del proyecto y deja trazada la última ejecución relevante.

No debe reemplazar:
- el roadmap de `plan_dev/MASTER_PLAN.md`
- el detalle operativo de `plan_dev/daily/`
- el backlog de `plan_dev/BACKLOG.md`

---

## Fecha de última actualización
2026-08-24 08:35 -03

## Resumen ejecutivo
- Estado general del proyecto: el motor conversacional sigue en progreso y ya soporta menus interactivos por paso para selecciones acotadas de WhatsApp, manteniendo fallback por texto/numero.
- Último bloque completado: preparacion del bootstrap de acceso operativo de
  testing con parametros movidos a inventory/Vault.
- Milestone actual: `M3 - Bootstrap y validacion remota de deploy` del daily
  2026-08-24 queda pendiente de ejecucion real contra testing.
- Próximo paso sugerido: cargar `vault_testing_bootstrap_become_password` en
  Vault, cargar `vault_testing_bastion_ssh_password` si el salto institucional
  pide password, y ejecutar `ansible-playbook -i inventories/testing/hosts.yml
  playbooks/bootstrap-access.yml` desde `deploy/provisioning`.
- Nota de seguridad operativa: `deploy/provisioning/group_vars/vault.yml` fue
  convertido a formato Ansible Vault; si los valores previos ya eran secretos
  reales, conviene rotarlos porque existian en commits anteriores.
- Nota de bootstrap SSH: el acceso inicial ahora usa el alias local
  `unlu-medicina-testing` y pisa explicitamente la key heredada para usar
  `~/.ssh/id_ed25519`, evitando que Ansible intente conectar como `mgaray` con
  la clave operativa `deploy_ed25519`.
- Nota de bastion: `ssh -o BatchMode=yes unlu-medicina-testing` confirmo que el
  corte `Connection closed by UNKNOWN port 65535` ocurre en el salto
  `martin@170.210.103.133` por password SSH no interactiva. Testing soporta
  `vault_testing_bastion_ssh_password` para que Ansible use `sshpass`.

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
- 2026-08-24 08:35 -03

### Plan diario usado
- `plan_dev/daily/2026-08-24.md`

### Milestone trabajado
- `M3 - Bootstrap y validacion remota de deploy`

### Resultado
- `needs_review`

### Resumen corto
- Se dejo `bootstrap-access.yml` parametrizado desde inventory/Vault para que
  testing pueda ejecutarse con el comando corto:
  `ansible-playbook -i inventories/testing/hosts.yml
  playbooks/bootstrap-access.yml`.
- El inventory de testing guarda las variables de acceso inicial; el playbook
  crea dinamicamente `bootstrap_targets` para entrar como `mgaray`, elevar con
  `su` y crear el usuario operativo `deploy`.
- Se convirtio `group_vars/vault.yml` a formato Ansible Vault porque existia
  como YAML plano y `ansible-vault edit` fallaba con `Input is not vault
  encrypted data`.
- Se corrigio el bootstrap inicial para apoyarse directamente en el alias SSH
  local `unlu-medicina-testing`, usando explicitamente `~/.ssh/id_ed25519` para
  no heredar `deploy_ed25519` desde `all.vars`. El `-vvvv` mostro que esa key
  operativa se estaba inyectando en la conexion bootstrap.
- Se agrego `bootstrap_access_initial_ssh_password` desde
  `vault_testing_bastion_ssh_password` para atravesar el ProxyJump cuando el
  bastion requiere password y no clave SSH.
- No se ejecuto el bootstrap remoto; falta cargar la clave de `su` en Vault.

---

## Cambios realizados
- archivos tocados: `deploy/provisioning/playbooks/bootstrap-access.yml`,
  `deploy/provisioning/inventories/testing/hosts.yml`,
  `deploy/provisioning/inventories/README.md`,
  `deploy/docs/deployment-guide.md`,
  `deploy/provisioning/group_vars/vault.yml`,
  `plan_dev/daily/2026-08-24.md` y status.
- resumen tecnico: `bootstrap-access.yml` ahora registra un host bootstrap
  dinamico desde variables de inventory/Vault, luego apunta a `bootstrap_targets`,
  valida opt-in, clave publica y password de `su` cuando corresponde, y toma
  usuario/grupo/sudo desde variables. Testing define el acceso inicial con
  `bootstrap_access_initial_user: mgaray`,
  `bootstrap_access_initial_become_method: su` y password desde
  `vault_testing_bootstrap_become_password`. El host bootstrap no define
  `ansible_ssh_common_args`; el salto queda delegado al alias
  `unlu-medicina-testing` de `~/.ssh/config`, pero la identidad SSH inicial se
  fija como `~/.ssh/id_ed25519`. Cuando
  `vault_testing_bastion_ssh_password` existe, `add_host` define
  `ansible_password` para habilitar `sshpass` en la conexion SSH.
- documentación actualizada: si; inventario y guia de deploy documentan el flujo
  con Vault y el comando corto.
- runtime Laravel/Docker modificado: no; sólo provisioning y documentación.
- diagramas actualizados: no; no cambió arquitectura runtime Laravel, flujos ni modelo de datos.

---

## Validaciones

### Automáticas
- tests de Laravel: no corresponden; no hubo cambios funcionales.
- checks:
  - `yamllint deploy/provisioning/inventories/testing/hosts.yml deploy/provisioning/playbooks/bootstrap-access.yml`
  - `ansible-playbook -i inventories/testing/hosts.yml playbooks/bootstrap-access.yml --syntax-check`
  - `ansible-inventory -i inventories/testing/hosts.yml --graph`
  - `ansible-inventory -i inventories/testing/hosts.yml --host avisos-testing`
  - `ansible-vault view group_vars/vault.yml`
  - `ansible-lint playbooks/bootstrap-access.yml`
  - `git diff --check`
- resultado: sin errores.
- observación: `ansible-lint` emite un warning de entorno por `PATH` del venv
  local, sin violaciones.

### Manuales sugeridas
- cargar `vault_testing_bootstrap_become_password` en
  `deploy/provisioning/group_vars/vault.yml` con `ansible-vault edit`.
- si el bastion pide password, cargar `vault_testing_bastion_ssh_password` en el
  mismo Vault.
- ejecutar `ansible-playbook -i inventories/testing/hosts.yml
  playbooks/bootstrap-access.yml` desde `deploy/provisioning`.
- validar `ssh unlu-medicina-testing-deploy 'whoami; sudo -n true; python3 --version'`.
- ejecutar `ansible -i inventories/testing/hosts.yml app_servers -m ping` antes de
  cualquier playbook remoto.
- revisar `site.yml --check --diff` antes de aplicar `site.yml`.

---

## Bloqueos actuales
- no avanzar a `site.yml --check --diff` ni `site.yml` real hasta cargar los
  secretos de bootstrap necesarios, completar bootstrap y validar SSH/sudo como
  `deploy`.
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
- continuar con `M3 - Bootstrap y validación remota de deploy` del daily
  2026-08-24.

---

## Referencia breve a backlog
- `LOG-001`: definir política de datos sensibles en logs quedó registrado como pendiente importante.
- `BO-001`: definido y marcado `done`; matriz en `docs/backoffice/permissions.md`.
- `BO-002`: definir estrategia de storage privado de certificados.
- `BO-003`: definir operación manual de asociaciones aviso-certificado.
- `BO-004`: definir futuro de `anticipos_certificado.aviso_id`.
