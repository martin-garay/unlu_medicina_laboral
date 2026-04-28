# Status

## Objetivo

Este archivo consolida el estado actual del proyecto y deja trazada la última ejecución relevante.

No debe reemplazar:
- el roadmap de `plan_dev/MASTER_PLAN.md`
- el detalle operativo de `plan_dev/daily/`
- el backlog de `plan_dev/BACKLOG.md`

---

## Fecha de última actualización
2026-04-28 15:05 -03

## Resumen ejecutivo
- Estado general del proyecto: la base tecnica del backoffice con Filament quedo instalada y validada.
- Último bloque completado: `D3 - Crear roles, permisos base y seed local`.
- Milestone actual: `D4 - Restringir acceso del panel por permiso`.
- Próximo paso sugerido: reemplazar `is_admin` por `backoffice.access` en `User::canAccessPanel()`.

---

## Estado global

### Documentación
- estado: `in_progress`
- notas: la estructura operativa nueva ya tiene roles, precedencia y prompt lanzador estándar. M5.4 sincronizó documentos de modelo, flujo, validaciones y decisiones técnicas para reflejar estado `inicial`, adjuntos múltiples y relación N a N antes de M6.

### Motor de conversación
- estado: `in_progress`
- notas: el motor ya tiene una capa común de interacción (`ConversationInteractionService`), lookup/alta por canal y una entrada interna de prueba sin depender de WhatsApp. Desde M2, toda conversación nueva responde bienvenida + menú y no interpreta el primer mensaje como selección de flujo.

### Flujos
- aviso: `in_progress`
- anticipo: `in_progress`
- notas: la decisión vigente es tomar `AnticipoCertificado` como entidad actual de certificado y no crear una entidad nueva. RF-03.1 quedó cubierto en M5.2: el flujo permite adjuntar hasta `medicina_laboral.certificados.max_files` archivos/imágenes antes de confirmar.

### Modelo de datos
- estado: `in_progress`
- notas: M5.1 implementó `avisos.estado` con default `inicial`, cambio de default de `anticipos_certificado.estado` a `inicial` y migración de anticipos existentes con estado `registrado`. M5.3 agregó la pivot `anticipo_certificado_aviso` para relación N a N, con backfill desde `anticipos_certificado.aviso_id` y compatibilidad temporal con la relación legacy.

### Testing
- estado: `in_progress`
- notas: D3 ejecuto `make test`: `133 passed`, `445 assertions`, incluyendo cobertura de seeder idempotente de roles/permisos y admin local.

### Inactividad / scheduler
- estado: `in_progress`
- notas: el scheduler de timeouts existe y no fue modificado en este milestone.

### Logs / operación
- estado: `in_progress`
- notas: M6 relevó logs actuales y clasificó estructura objetivo en debug, operación, auditoría y métricas. La política de datos sensibles en logs queda como pendiente importante en `plan_dev/BACKLOG.md` (`LOG-001`), por decisión humana no se implementa todavía.

### Admin / roles / permisos
- estado: `in_progress`
- notas: I1 instalo Filament `v5.6.1`, agrego panel base en `/admin`, auth minima con `App\Models\User` y restriccion por `is_admin`. D1 resolvio `BO-001`. D2 instalo Spatie Laravel Permission `6.25.0`. D3 agrego matriz en `config/backoffice.php` y seeder idempotente de roles/permisos/admin local. Falta reemplazar `is_admin` por `backoffice.access`.

### Integraciones futuras
- estado: `pending`
- notas: siguen planteadas como desacopladas y futuras.

### Deploy / Ansible
- estado: `pending`
- notas: no existe todavía base Ansible/Vagrant; el daily 2026-04-24 lo trata como frente separado.

---

## Última ejecución del agente

### Fecha/hora
- 2026-04-28 15:05 -03

### Plan diario usado
- `plan_dev/daily/2026-04-28.md`

### Milestone trabajado
- `D3 - Crear roles, permisos base y seed local`

### Resultado
- `done`

### Resumen corto
- se agrego config de backoffice, seeder idempotente de roles/permisos y usuario admin local de prueba.

---

## Cambios realizados
- archivos tocados: `config/backoffice.php`, `database/seeders/BackofficeRolesAndPermissionsSeeder.php`, `composer.json`, `.env.example`, `.env.docker.example`, `.env` local no versionado, `README.md`, `tests/Feature/Backoffice/BackofficeRolesAndPermissionsSeederTest.php`, `plan_dev/daily/2026-04-28.md` y `plan_dev/STATUS.md`
- resumen técnico: se materializo la matriz de permisos en configuracion, se agrego seeder idempotente y se actualizo el flujo local para crear admin con rol.
- documentación actualizada: sí, README, daily y estado consolidado
- diagramas actualizados: no aplica

---

## Validaciones

### Automáticas
- tests corridos: `make test`
- resultado: `133 passed`, `445 assertions`
- otros checks: `composer dump-autoload --no-scripts`, `make artisan CMD='config:clear'`, `make artisan CMD='db:seed --class=Database\\Seeders\\BackofficeRolesAndPermissionsSeeder'`, `git diff --check`
- resultado: sin errores

### Manuales sugeridas
- ejecutar `D4` para reemplazar `is_admin` por permiso real.
- validar login con `admin@admin.com` luego de D4.

---

## Bloqueos actuales
- `I4` depende de `BO-002`
- `I7` depende de `BO-003`

---

## Decisiones humanas pendientes
- confirmar si avisos `observado` deben poder recibir anticipo o si pasan a ser bloqueantes
- confirmar si futuras asociaciones adicionales de avisos serán manuales, automáticas o mixtas
- confirmar si `anticipos_certificado.aviso_id` quedará como cache del primer aviso o se eliminará luego de migrar lecturas a pivot
- confirmar estrategia de storage privado para certificados médicos

---

## Próximo milestone recomendado
- ejecutar `D4 - Restringir acceso del panel por permiso` del daily `plan_dev/daily/2026-04-28.md`

---

## Referencia breve a backlog
- `LOG-001`: definir política de datos sensibles en logs quedó registrado como pendiente importante.
- `BO-001`: definido y marcado `done`; matriz en `docs/backoffice/permissions.md`.
- `BO-002`: definir estrategia de storage privado de certificados.
- `BO-003`: definir operación manual de asociaciones aviso-certificado.
- `BO-004`: definir futuro de `anticipos_certificado.aviso_id`.
