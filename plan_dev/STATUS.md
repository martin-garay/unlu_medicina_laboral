# Status

## Objetivo

Este archivo consolida el estado actual del proyecto y deja trazada la última ejecución relevante.

No debe reemplazar:
- el roadmap de `plan_dev/MASTER_PLAN.md`
- el detalle operativo de `plan_dev/daily/`
- el backlog de `plan_dev/BACKLOG.md`

---

## Fecha de última actualización
2026-04-28 14:50 -03

## Resumen ejecutivo
- Estado general del proyecto: la base tecnica del backoffice con Filament quedo instalada y validada.
- Último bloque completado: `D2 - Instalar y configurar stack de permisos`.
- Milestone actual: `D3 - Crear roles, permisos base y seed local`.
- Próximo paso sugerido: crear seeders idempotentes para roles/permisos base y usuario admin local.

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
- notas: D2 ejecuto `make test`: `131 passed`, `433 assertions`, incluyendo cobertura basica de acceso al panel Filament y stack Spatie.

### Inactividad / scheduler
- estado: `in_progress`
- notas: el scheduler de timeouts existe y no fue modificado en este milestone.

### Logs / operación
- estado: `in_progress`
- notas: M6 relevó logs actuales y clasificó estructura objetivo en debug, operación, auditoría y métricas. La política de datos sensibles en logs queda como pendiente importante en `plan_dev/BACKLOG.md` (`LOG-001`), por decisión humana no se implementa todavía.

### Admin / roles / permisos
- estado: `in_progress`
- notas: I1 instalo Filament `v5.6.1`, agrego panel base en `/admin`, auth minima con `App\Models\User` y restriccion por `is_admin`. D1 resolvio `BO-001`. D2 instalo Spatie Laravel Permission `6.25.0`, publico config/migration e integro `HasRoles` en `User`. Falta materializar roles/permisos en seeders y reemplazar `is_admin` por `backoffice.access`.

### Integraciones futuras
- estado: `pending`
- notas: siguen planteadas como desacopladas y futuras.

### Deploy / Ansible
- estado: `pending`
- notas: no existe todavía base Ansible/Vagrant; el daily 2026-04-24 lo trata como frente separado.

---

## Última ejecución del agente

### Fecha/hora
- 2026-04-28 14:50 -03

### Plan diario usado
- `plan_dev/daily/2026-04-28.md`

### Milestone trabajado
- `D2 - Instalar y configurar stack de permisos`

### Resultado
- `done`

### Resumen corto
- se instalo Spatie Laravel Permission, se publicaron config/migrations y se integro `HasRoles` en `App\Models\User`.

---

## Cambios realizados
- archivos tocados: `composer.json`, `composer.lock`, `config/permission.php`, `database/migrations/2026_04_28_173621_create_permission_tables.php`, `app/Models/User.php`, `tests/Feature/Backoffice/PermissionStackTest.php`, `plan_dev/daily/2026-04-28.md` y `plan_dev/STATUS.md`
- resumen técnico: se agrego la dependencia de permisos, tablas de Spatie, trait `HasRoles` y tests de asignacion rol-permiso con guard `web`.
- documentación actualizada: sí, daily y estado consolidado
- diagramas actualizados: no aplica

---

## Validaciones

### Automáticas
- tests corridos: `make test`
- resultado: `131 passed`, `433 assertions`
- otros checks: `make artisan CMD='config:clear'`, `make migrate`, `git diff --check`
- resultado: sin errores

### Manuales sugeridas
- ejecutar `D3` para crear seeders idempotentes de roles/permisos.
- luego validar login admin una vez que `D4` reemplace `is_admin` por permiso.

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
- ejecutar `D3 - Crear roles, permisos base y seed local` del daily `plan_dev/daily/2026-04-28.md`

---

## Referencia breve a backlog
- `LOG-001`: definir política de datos sensibles en logs quedó registrado como pendiente importante.
- `BO-001`: definido y marcado `done`; matriz en `docs/backoffice/permissions.md`.
- `BO-002`: definir estrategia de storage privado de certificados.
- `BO-003`: definir operación manual de asociaciones aviso-certificado.
- `BO-004`: definir futuro de `anticipos_certificado.aviso_id`.
