# Status

## Objetivo

Este archivo consolida el estado actual del proyecto y deja trazada la última ejecución relevante.

No debe reemplazar:
- el roadmap de `plan_dev/MASTER_PLAN.md`
- el detalle operativo de `plan_dev/daily/`
- el backlog de `plan_dev/BACKLOG.md`

---

## Fecha de última actualización
2026-04-26 02:57 -03

## Resumen ejecutivo
- Estado general del proyecto: se abrió el `daily` del 2026-04-26 para retomar M5 antes de avanzar a logs/admin/Ansible.
- Último bloque completado: `M5.1` del daily 2026-04-26, implementando estado `inicial` en avisos y anticipos/certificados.
- Milestone actual: próximo milestone pendiente `M5.2 - Permitir hasta 3 adjuntos antes de confirmar certificado`.
- Próximo paso sugerido: ejecutar M5.2 y no avanzar a M6 hasta cerrar o bloquear explícitamente la cadena M5.x.

---

## Estado global

### Documentación
- estado: `in_progress`
- notas: la estructura operativa nueva ya tiene roles, precedencia y prompt lanzador estándar; el daily 2026-04-26 retoma M5 y ordena la implementación de estado `inicial`, adjuntos múltiples y relación N a N antes de M6. Sigue pendiente sincronizar por completo documentos que describen el anticipo como parcial.

### Motor de conversación
- estado: `in_progress`
- notas: el motor ya tiene una capa común de interacción (`ConversationInteractionService`), lookup/alta por canal y una entrada interna de prueba sin depender de WhatsApp. Desde M2, toda conversación nueva responde bienvenida + menú y no interpreta el primer mensaje como selección de flujo.

### Flujos
- aviso: `in_progress`
- anticipo: `in_progress`
- notas: la decisión vigente es tomar `AnticipoCertificado` como entidad actual de certificado y no crear una entidad nueva. RF-03.1 queda pendiente porque el flujo actual pasa a confirmación después del primer adjunto y debe evolucionar para aceptar hasta 3 adjuntos antes de confirmar.

### Modelo de datos
- estado: `in_progress`
- notas: M5.1 implementó `avisos.estado` con default `inicial`, cambio de default de `anticipos_certificado.estado` a `inicial` y migración de anticipos existentes con estado `registrado`. La asociación N avisos mediante pivot queda planificada para M5.3.

### Testing
- estado: `in_progress`
- notas: M5.1 ejecutó `make test`: `115 passed`, `385 assertions`.

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
- 2026-04-26 02:57 -03

### Plan diario usado
- `plan_dev/daily/2026-04-26.md`

### Milestone trabajado
- `M5.1 - Implementar estado inicial en avisos y anticipos`

### Resultado
- `done`

### Resumen corto
- se agregó soporte runtime para estado `inicial` en avisos y anticipos/certificados, incluyendo migración, servicios, tests y diagramas.

---

## Cambios realizados
- archivos tocados: migración `2026_04_26_000006_add_initial_status_to_business_records.php`, modelos/servicios de aviso y anticipo, esquema auxiliar de tests, tests de servicios, DBML, diagrama de clases, daily y status
- resumen técnico: avisos y anticipos/certificados nacen en estado `inicial`; anticipos existentes en `registrado` se migran a `inicial`; se preserva `registrado_en` como timestamp de recepción.
- documentación actualizada: sí, planificación operativa y estado consolidado
- diagramas actualizados: sí, DBML y clase `Aviso`/`AnticipoCertificado`

---

## Validaciones

### Automáticas
- tests corridos: `make test`
- resultado: `115 passed`, `385 assertions`
- otros checks: `php artisan migrate --pretend`, `make diagrams`, `git diff --check`
- resultado: SQL esperado generado para PostgreSQL; diagramas regenerados; sin errores de whitespace

### Manuales sugeridas
- recorrer flujo de aviso y anticipo en canal interno y confirmar que las entidades quedan en `inicial`
- revisar si `aviso_id` debe quedar como cache del primer aviso o eliminarse luego de migrar lecturas a la pivot

---

## Bloqueos actuales
- M6 queda postergado operativamente hasta cerrar o bloquear explícitamente la cadena M5.x del daily 2026-04-26
- falta definir matriz mínima de permisos para `auditor` y `director`

---

## Decisiones humanas pendientes
- definir matriz mínima de permisos para `auditor` y `director`
- confirmar si avisos `observado` deben poder recibir anticipo o si pasan a ser bloqueantes
- confirmar si futuras asociaciones adicionales de avisos serán manuales, automáticas o mixtas
- confirmar si `anticipos_certificado.aviso_id` quedará como cache del primer aviso o se eliminará luego de migrar lecturas a pivot

---

## Próximo milestone recomendado
- ejecutar `M5.2` de `plan_dev/daily/2026-04-26.md`: permitir hasta 3 adjuntos antes de confirmar certificado

---

## Referencia breve a backlog
- `LOG-001`: definir política de datos sensibles en logs quedó registrado como pendiente importante.
