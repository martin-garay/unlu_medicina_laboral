# Status

## Objetivo

Este archivo consolida el estado actual del proyecto y deja trazada la última ejecución relevante.

No debe reemplazar:
- el roadmap de `plan_dev/MASTER_PLAN.md`
- el detalle operativo de `plan_dev/daily/`
- el backlog de `plan_dev/BACKLOG.md`

---

## Fecha de última actualización
2026-04-26 03:26 -03

## Resumen ejecutivo
- Estado general del proyecto: se abrió el `daily` del 2026-04-26 para retomar M5 antes de avanzar a logs/admin/Ansible.
- Último bloque completado: `M5.3` del daily 2026-04-26, implementando relación N a N entre avisos y anticipos/certificados.
- Milestone actual: próximo milestone pendiente `M5.4 - Sincronizar documentación y diagramas de anticipo/certificado`.
- Próximo paso sugerido: ejecutar M5.4 y no avanzar a M6 hasta cerrar o bloquear explícitamente la cadena M5.x.

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
- 2026-04-26 03:26 -03

### Plan diario usado
- `plan_dev/daily/2026-04-26.md`

### Milestone trabajado
- `M5.3 - Implementar relación N a N aviso-certificado`

### Resultado
- `done`

### Resumen corto
- se agregó la tabla pivot aviso-anticipo/certificado, relaciones Eloquent N a N y escritura del vínculo al materializar el anticipo.

---

## Cambios realizados
- archivos tocados: migración `2026_04_26_000007_create_anticipo_certificado_aviso_table.php`, modelos `Aviso`/`AnticipoCertificado`, `AnticipoCertificadoService`, esquema auxiliar de tests, tests de servicio y migración, DBML, diagrama de clases, daily y status
- resumen técnico: se mantiene `anticipos_certificado.aviso_id` como compatibilidad temporal y se agrega pivot N a N con backfill; el servicio escribe ambos vínculos al crear anticipos desde conversación.
- documentación actualizada: sí, planificación operativa y estado consolidado
- diagramas actualizados: sí, DBML y diagrama de clases

---

## Validaciones

### Automáticas
- tests corridos: `make test`
- resultado: `124 passed`, `420 assertions`
- otros checks: `php artisan migrate --pretend`, `make diagrams`
- resultado: SQL esperado generado para PostgreSQL; diagramas regenerados

### Manuales sugeridas
- crear anticipo desde flujo y verificar que el aviso inicial quede tanto en `aviso_id` como en `anticipo_certificado_aviso`
- crear manualmente un segundo vínculo y verificar navegación por relaciones Eloquent
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
- ejecutar `M5.4` de `plan_dev/daily/2026-04-26.md`: sincronizar documentación y diagramas de anticipo/certificado

---

## Referencia breve a backlog
- `LOG-001`: definir política de datos sensibles en logs quedó registrado como pendiente importante.
