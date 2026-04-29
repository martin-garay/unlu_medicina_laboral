# Status

## Objetivo

Este archivo consolida el estado actual del proyecto y deja trazada la última ejecución relevante.

No debe reemplazar:
- el roadmap de `plan_dev/MASTER_PLAN.md`
- el detalle operativo de `plan_dev/daily/`
- el backlog de `plan_dev/BACKLOG.md`

---

## Fecha de última actualización
2026-04-29 01:28 -03

## Resumen ejecutivo
- Estado general del proyecto: la base tecnica del backoffice con Filament, permisos y auditoria administrativa base quedo instalada y validada. La planificación fina de módulos read-only quedó documentada, el primer Resource read-only fue implementado y la regla de `admin` total en desarrollo quedó explicitada.
- Último bloque completado: documentación de alcance para `P3 - Agregar visualización de historial de conversación`.
- Milestone actual: `P3 - Agregar visualización de historial de conversación`.
- Próximo paso sugerido: implementar acción de ojo y pantalla de historial de conversaciones con mensajes/eventos, sin exponer `payload_crudo` completo ni metadata completa.

---

## Estado global

### Documentación
- estado: `in_progress`
- notas: la estructura operativa nueva ya tiene roles, precedencia y prompt lanzador estándar. La documentación de backoffice ya registra I1, I2 base, I3 base y especificación fina de módulos administrativos en `docs/backoffice/module-specs.md`.

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
- notas: I1 instalo Filament `v5.6.1`, agrego panel base en `/admin` y auth minima con `App\Models\User`. I2 base quedo cerrado: Spatie Laravel Permission `6.25.0`, matriz en `config/backoffice.php`, seeder idempotente de roles/permisos/admin local y acceso al panel por `backoffice.access`. En desarrollo, `admin` debe sincronizar todos los permisos definidos; eso no saltea restricciones read-only de cada Resource. Desde P2 existe `ConversacionResource` read-only protegido por `conversaciones.view`. P3 queda especificado con permiso nuevo `conversaciones.historial.view` para acción de ojo y pantalla de historial. No incluye UI de gestion de usuarios/roles.

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
- 2026-04-29 01:28 -03

### Plan diario usado
- `plan_dev/daily/2026-04-29.md`

### Milestone trabajado
- Documentación de alcance para `P3 - Agregar visualización de historial de conversación`

### Resultado
- `done`

### Resumen corto
- se detallo el próximo milestone para agregar una acción de ojo que abra el historial completo de mensajes y eventos de una conversación.

---

## Cambios realizados
- archivos tocados: `plan_dev/daily/2026-04-29.md`, `docs/backoffice/module-specs.md`, `docs/backoffice/permissions.md` y `plan_dev/STATUS.md`
- resumen técnico: se especifico `conversaciones.historial.view` como permiso para historial/detalle, la acción visual de ojo, la pantalla read-only del hilo usuario/chatbot, los campos seguros de mensajes/eventos y los tests esperados.
- documentación actualizada: sí, daily y estado consolidado
- diagramas actualizados: no aplica

---

## Validaciones

### Automáticas
- tests corridos: no aplica, cambio documental
- resultado: no aplica
- otros checks: `git diff --check`
- resultado: sin errores

### Manuales sugeridas
- confirmar el nombre del permiso `conversaciones.historial.view` antes de implementar P3 si se prefiere otra nomenclatura.
- revisar visualmente `/admin/conversaciones` con un usuario `admin`, `auditor` o `director` luego de implementar P3.
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
- ejecutar `P3 - Agregar visualización de historial de conversación` del daily `plan_dev/daily/2026-04-29.md`.

---

## Referencia breve a backlog
- `LOG-001`: definir política de datos sensibles en logs quedó registrado como pendiente importante.
- `BO-001`: definido y marcado `done`; matriz en `docs/backoffice/permissions.md`.
- `BO-002`: definir estrategia de storage privado de certificados.
- `BO-003`: definir operación manual de asociaciones aviso-certificado.
- `BO-004`: definir futuro de `anticipos_certificado.aviso_id`.
