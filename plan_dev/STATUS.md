# Status

## Objetivo

Este archivo consolida el estado actual del proyecto y deja trazada la última ejecución relevante.

No debe reemplazar:
- el roadmap de `plan_dev/MASTER_PLAN.md`
- el detalle operativo de `plan_dev/daily/`
- el backlog de `plan_dev/BACKLOG.md`

---

## Fecha de última actualización
2026-04-28 23:18 -03

## Resumen ejecutivo
- Estado general del proyecto: la base tecnica del backoffice con Filament y permisos quedo instalada y validada; `I3 - Auditoria administrativa base` ya tiene contrato documental, tabla, modelo y servicio interno inicial.
- Último bloque completado: `A3 - Crear servicio de auditoria administrativa`.
- Milestone actual: `A4 - Integrar auditoria en eventos administrativos base`.
- Próximo paso sugerido: evaluar `A4`; si no hay evento administrativo real para integrar sin forzar diseño, marcarlo `needs_review` y cerrar con el servicio listo.

---

## Estado global

### Documentación
- estado: `in_progress`
- notas: la estructura operativa nueva ya tiene roles, precedencia y prompt lanzador estándar. La documentación de backoffice ya registra I1 e I2 base, incluyendo permisos y cierre de alcance.

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
- notas: A3 ejecuto `make test`: `141 passed`, `468 assertions`, incluyendo modelo y servicio de auditoria administrativa.

### Inactividad / scheduler
- estado: `in_progress`
- notas: el scheduler de timeouts existe y no fue modificado en este milestone.

### Logs / operación
- estado: `in_progress`
- notas: M6 relevó logs actuales y clasificó estructura objetivo en debug, operación, auditoría y métricas. La política de datos sensibles en logs queda como pendiente importante en `plan_dev/BACKLOG.md` (`LOG-001`), por decisión humana no se implementa todavía.

### Admin / roles / permisos
- estado: `base_done`
- notas: I1 instalo Filament `v5.6.1`, agrego panel base en `/admin` y auth minima con `App\Models\User`. I2 base quedo cerrado: Spatie Laravel Permission `6.25.0`, matriz en `config/backoffice.php`, seeder idempotente de roles/permisos/admin local y acceso al panel por `backoffice.access`. No incluye UI de gestion de usuarios/roles.

### Auditoria administrativa
- estado: `in_progress`
- notas: A1 definio el contrato minimo de evento administrativo en `docs/backoffice/security-and-audit.md`. A2 agrego la tabla `auditoria_administrativa`, el modelo `App\Models\AuditoriaAdministrativa`, tests de persistencia/casts/relaciones y actualizacion del DBML. A3 agrego `App\Domain\Auditoria\Services\AuditoriaAdministrativaService` con validacion de origenes y accion.

### Integraciones futuras
- estado: `pending`
- notas: siguen planteadas como desacopladas y futuras.

### Deploy / Ansible
- estado: `pending`
- notas: no existe todavía base Ansible/Vagrant; el daily 2026-04-24 lo trata como frente separado.

---

## Última ejecución del agente

### Fecha/hora
- 2026-04-28 23:18 -03

### Plan diario usado
- `plan_dev/daily/2026-04-28-02-backoffice-auditoria.md`

### Milestone trabajado
- `A3 - Crear servicio de auditoria administrativa`

### Resultado
- `done`

### Resumen corto
- se agrego el servicio interno para registrar eventos de auditoria administrativa sin acoplar futuras acciones a Filament ni al modelo directamente.

---

## Cambios realizados
- archivos tocados: `app/Domain/Auditoria/Services/AuditoriaAdministrativaService.php`, `tests/Feature/Domain/Auditoria/AuditoriaAdministrativaServiceTest.php`, `docs/backoffice/security-and-audit.md`, `plan_dev/daily/2026-04-28-02-backoffice-auditoria.md` y `plan_dev/STATUS.md`
- resumen técnico: el servicio `AuditoriaAdministrativaService` registra eventos con actor nullable, origen controlado, entidad auditable opcional y metadata/before/after minimizados. Rechaza acciones vacias y origenes no soportados.
- documentación actualizada: sí, daily y estado consolidado
- diagramas actualizados: no aplica

---

## Validaciones

### Automáticas
- tests corridos: `make test`
- resultado: `make test`: `141 passed`, `468 assertions`
- otros checks: `git diff --check`
- resultado: sin errores

### Manuales sugeridas
- revisar que el servicio sea suficiente para futuras Application Actions de certificados, avisos y asociaciones.
- no avanzar a Resources ni storage privado antes de cerrar I3.

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
- ejecutar `A4 - Integrar auditoria en eventos administrativos base` del daily `plan_dev/daily/2026-04-28-02-backoffice-auditoria.md`, o marcarlo `needs_review` si no hay evento administrativo real sin forzar diseño.

---

## Referencia breve a backlog
- `LOG-001`: definir política de datos sensibles en logs quedó registrado como pendiente importante.
- `BO-001`: definido y marcado `done`; matriz en `docs/backoffice/permissions.md`.
- `BO-002`: definir estrategia de storage privado de certificados.
- `BO-003`: definir operación manual de asociaciones aviso-certificado.
- `BO-004`: definir futuro de `anticipos_certificado.aviso_id`.
