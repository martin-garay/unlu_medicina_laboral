# Status

## Objetivo

Este archivo consolida el estado actual del proyecto y deja trazada la última ejecución relevante.

No debe reemplazar:
- el roadmap de `plan_dev/MASTER_PLAN.md`
- el detalle operativo de `plan_dev/daily/`
- el backlog de `plan_dev/BACKLOG.md`

---

## Fecha de última actualización
2026-04-26 03:35 -03

## Resumen ejecutivo
- Estado general del proyecto: se abrió el `daily` del 2026-04-26 para retomar M5 antes de avanzar a logs/admin/Ansible.
- Último bloque completado: `M5.4` del daily 2026-04-26, sincronizando documentación de anticipo/certificado con RF-03, RF-03.1 y RF-03.2.
- Milestone actual: cadena M5.x cerrada; próximo milestone recomendado `M6 - Relevar logs actuales y definir estructura objetivo`.
- Próximo paso sugerido: ejecutar M6 del daily 2026-04-26.

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
- notas: la política de datos sensibles en logs queda como pendiente importante en `plan_dev/BACKLOG.md` (`LOG-001`), por decisión humana no se implementa todavía.

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
- 2026-04-26 03:35 -03

### Plan diario usado
- `plan_dev/daily/2026-04-26.md`

### Milestone trabajado
- `M5.4 - Sincronizar documentación y diagramas de anticipo/certificado`

### Resultado
- `done`

### Resumen corto
- se alineó la documentación viva con la implementación de RF-03, RF-03.1 y RF-03.2.

---

## Cambios realizados
- archivos tocados: `docs/04-modelo-de-datos.md`, `docs/07-flujo-anticipo-certificado.md`, `docs/08-validaciones-y-reglas.md`, `docs/12-decisiones-tecnicas.md`, daily y status
- resumen técnico: la documentación describe el estado real al 2026-04-26: avisos y anticipos nacen en `inicial`, el flujo permite hasta 3 adjuntos configurables antes de confirmar y la relación aviso-anticipo/certificado es N a N mediante pivot con `aviso_id` legacy temporal.
- documentación actualizada: sí, planificación operativa y estado consolidado
- diagramas actualizados: verificados; las fuentes y renders estructurales quedaron actualizados en M5.3

---

## Validaciones

### Automáticas
- tests corridos: `make test`
- resultado: `124 passed`, `420 assertions`
- otros checks: `make diagrams-check`, `git diff --check`
- resultado: diagramas sincronizados; sin errores de whitespace

### Manuales sugeridas
- revisar que RF-03, RF-03.1 y RF-03.2 puedan mapearse desde docs a código y tests
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
- ejecutar `M6` de `plan_dev/daily/2026-04-26.md`: relevar logs actuales y definir estructura objetivo

---

## Referencia breve a backlog
- `LOG-001`: definir política de datos sensibles en logs quedó registrado como pendiente importante.
