# Status

## Objetivo

Este archivo consolida el estado actual del proyecto y deja trazada la última ejecución relevante.

No debe reemplazar:
- el roadmap de `plan_dev/MASTER_PLAN.md`
- el detalle operativo de `plan_dev/daily/`
- el backlog de `plan_dev/BACKLOG.md`

---

## Fecha de última actualización
2026-04-29 01:50 -03

## Resumen ejecutivo
- Estado general del proyecto: la base tecnica del backoffice con Filament, permisos y auditoria administrativa base quedo instalada y validada. La planificación fina de módulos read-only quedó documentada, el primer Resource read-only fue implementado y ya existe pantalla de historial de conversación.
- Último bloque completado: `P0 - Abrir daily de continuación read-only`.
- Milestone actual: `P1 - Agregar filtros y búsqueda avanzada a ConversacionResource` en `plan_dev/daily/2026-04-29-02-backoffice-readonly-modules.md`.
- Próximo paso sugerido: ejecutar P1 de la daily read-only.

---

## Estado global

### Documentación
- estado: `in_progress`
- notas: la estructura operativa nueva ya tiene roles, precedencia y prompt lanzador estándar. La documentación de backoffice ya registra I1, I2 base, I3 base y especificación fina de módulos administrativos en `docs/backoffice/module-specs.md`. Quedaron creadas dailies separadas para módulos read-only, usuarios/roles, dashboard/reportes y storage/configuración.

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
- notas: A4 ejecuto `make test`: `141 passed`, `472 assertions`, incluyendo modelo, servicio e integracion del seeder con auditoria administrativa.

### Inactividad / scheduler
- estado: `in_progress`
- notas: el scheduler de timeouts existe y no fue modificado en este milestone.

### Logs / operación
- estado: `in_progress`
- notas: M6 relevó logs actuales y clasificó estructura objetivo en debug, operación, auditoría y métricas. La política de datos sensibles en logs queda como pendiente importante en `plan_dev/BACKLOG.md` (`LOG-001`), por decisión humana no se implementa todavía.

### Admin / roles / permisos
- estado: `base_done`
- notas: I1 instalo Filament `v5.6.1`, agrego panel base en `/admin` y auth minima con `App\Models\User`. I2 base quedo cerrado: Spatie Laravel Permission `6.25.0`, matriz en `config/backoffice.php`, seeder idempotente de roles/permisos/admin local y acceso al panel por `backoffice.access`. En desarrollo, `admin` debe sincronizar todos los permisos definidos; eso no saltea restricciones read-only de cada Resource. Desde P2 existe `ConversacionResource` read-only protegido por `conversaciones.view`. Desde P3 existe permiso `conversaciones.historial.view`, acción de ojo y pantalla de historial read-only. No incluye UI de gestion de usuarios/roles.

### Auditoria administrativa
- estado: `base_done`
- notas: I3 base quedo cerrado. Existe contrato documental, tabla `auditoria_administrativa`, modelo `App\Models\AuditoriaAdministrativa`, servicio `App\Domain\Auditoria\Services\AuditoriaAdministrativaService`, tests y una integracion inicial en el seeder de roles/permisos con eventos `permissions.seeded` y `roles.seeded`.

### Integraciones futuras
- estado: `pending`
- notas: siguen planteadas como desacopladas y futuras.

### Deploy / Ansible
- estado: `pending`
- notas: no existe todavía base Ansible/Vagrant; el daily 2026-04-24 lo trata como frente separado.

---

## Última ejecución del agente

### Fecha/hora
- 2026-04-29 01:50 -03

### Plan diario usado
- `plan_dev/daily/2026-04-29-02-backoffice-readonly-modules.md`

### Milestone trabajado
- `P0 - Abrir daily de continuación read-only`

### Resultado
- `done`

### Resumen corto
- se crearon dailies separadas para continuar los frentes pendientes del backoffice y se dejó activa la daily de módulos read-only.

---

## Cambios realizados
- archivos tocados: `plan_dev/daily/2026-04-29-02-backoffice-readonly-modules.md`, `plan_dev/daily/2026-04-29-03-backoffice-users-roles.md`, `plan_dev/daily/2026-04-29-04-backoffice-dashboard-reportes.md`, `plan_dev/daily/2026-04-29-05-backoffice-storage-configuracion.md` y `plan_dev/STATUS.md`
- resumen técnico: se separaron los pendientes en planes operativos independientes y se marcó como próximo corte `P1 - Agregar filtros y búsqueda avanzada a ConversacionResource`.
- documentación actualizada: sí, daily y estado consolidado
- diagramas actualizados: no aplica

---

## Validaciones

### Automáticas
- tests corridos: no aplica, cambio documental/operativo
- resultado: no aplica
- otros checks: `git diff --check`
- resultado: sin errores

### Manuales sugeridas
- revisar el orden de dailies creado.
- confirmar si el próximo frente posterior a read-only debe ser usuarios/roles o dashboard/reportes.
- no implementar `I4` ni descarga/visualización de archivos médicos.

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
- ejecutar `P1 - Agregar filtros y búsqueda avanzada a ConversacionResource` en `plan_dev/daily/2026-04-29-02-backoffice-readonly-modules.md`.

---

## Referencia breve a backlog
- `LOG-001`: definir política de datos sensibles en logs quedó registrado como pendiente importante.
- `BO-001`: definido y marcado `done`; matriz en `docs/backoffice/permissions.md`.
- `BO-002`: definir estrategia de storage privado de certificados.
- `BO-003`: definir operación manual de asociaciones aviso-certificado.
- `BO-004`: definir futuro de `anticipos_certificado.aviso_id`.
