# Status

## Objetivo

Este archivo consolida el estado actual del proyecto y deja trazada la última ejecución relevante.

No debe reemplazar:
- el roadmap de `plan_dev/MASTER_PLAN.md`
- el detalle operativo de `plan_dev/daily/`
- el backlog de `plan_dev/BACKLOG.md`

---

## Fecha de última actualización
2026-04-26 03:48 -03

## Resumen ejecutivo
- Estado general del proyecto: se abrió el `daily` del 2026-04-26 para retomar M5 antes de avanzar a logs/admin/Ansible.
- Último bloque completado: `M6` del daily 2026-04-26, relevando logs actuales y definiendo estructura objetivo de trazabilidad.
- Milestone actual: próximo milestone recomendado `M7 - Diseñar admin, roles y permisos sin permisos por campo`.
- Próximo paso sugerido: ejecutar M7 del daily 2026-04-24/2026-04-26 si se decide avanzar con admin.

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
- notas: M5.3 ejecutó `make test`: `124 passed`, `420 assertions`.

### Inactividad / scheduler
- estado: `in_progress`
- notas: el scheduler de timeouts existe y no fue modificado en este milestone.

### Logs / operación
- estado: `in_progress`
- notas: M6 relevó logs actuales y clasificó estructura objetivo en debug, operación, auditoría y métricas. La política de datos sensibles en logs queda como pendiente importante en `plan_dev/BACKLOG.md` (`LOG-001`), por decisión humana no se implementa todavía.

### Admin / roles / permisos
- estado: `pending`
- notas: se definió que no se implementarán permisos por columna/campo; el diseño debe concentrarse en módulo, acción y entidad/recurso.

### Integraciones futuras
- estado: `pending`
- notas: siguen planteadas como desacopladas y futuras.

### Deploy / Ansible
- estado: `pending`
- notas: no existe todavía base Ansible/Vagrant; el daily 2026-04-24 lo trata como frente separado.

---

## Última ejecución del agente

### Fecha/hora
- 2026-04-26 03:48 -03

### Plan diario usado
- `plan_dev/daily/2026-04-26.md`

### Milestone trabajado
- `M6 - Relevar logs actuales y definir estructura objetivo`

### Resultado
- `done`

### Resumen corto
- se relevó el uso actual de logs y eventos, se documentaron gaps de operación/auditoría y se dejó una estructura objetivo incremental.

---

## Cambios realizados
- archivos tocados: `docs/12-decisiones-tecnicas.md`, `plan_dev/BACKLOG.md`, daily y status
- resumen técnico: se clasificaron logs existentes, se identificaron riesgos de payloads completos/datos sensibles y se propuso evolucionar hacia categorías debug, operación, auditoría y métricas.
- documentación actualizada: sí, planificación operativa y estado consolidado
- diagramas actualizados: no aplica en M6

---

## Validaciones

### Automáticas
- tests corridos: no aplica, milestone documental/analítico sin cambios runtime
- resultado: no aplica
- otros checks: `git diff --check`
- resultado: sin errores de whitespace

### Manuales sugeridas
- revisar gap analysis de logs y validar categorías objetivo antes de implementar redacción/minimización
- revisar si `aviso_id` debe quedar como cache del primer aviso o eliminarse luego de migrar lecturas a la pivot

---

## Bloqueos actuales
- falta definir matriz mínima de permisos para `auditor` y `director`

---

## Decisiones humanas pendientes
- definir matriz mínima de permisos para `auditor` y `director`
- confirmar si avisos `observado` deben poder recibir anticipo o si pasan a ser bloqueantes
- confirmar si futuras asociaciones adicionales de avisos serán manuales, automáticas o mixtas
- confirmar si `anticipos_certificado.aviso_id` quedará como cache del primer aviso o se eliminará luego de migrar lecturas a pivot

---

## Próximo milestone recomendado
- ejecutar `M7`: diseñar admin, roles y permisos sin permisos por campo

---

## Referencia breve a backlog
- `LOG-001`: definir política de datos sensibles en logs quedó registrado como pendiente importante.
