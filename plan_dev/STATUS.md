# Status

## Objetivo

Este archivo consolida el estado actual del proyecto y deja trazada la última ejecución relevante.

No debe reemplazar:
- el roadmap de `plan_dev/MASTER_PLAN.md`
- el detalle operativo de `plan_dev/daily/`
- el backlog de `plan_dev/BACKLOG.md`

---

## Fecha de última actualización
2026-04-24 03:18 -03

## Resumen ejecutivo
- Estado general del proyecto: el `daily` del 2026-04-24 quedó abierto con prioridades actualizadas sobre motor conversacional, estados de negocio, logs, admin y Ansible.
- Último bloque completado: `M4` del daily 2026-04-24, diseñando estados de avisos y anticipos.
- Milestone actual: próximo milestone pendiente `M5 - Diseñar relación de AnticipoCertificado con múltiples avisos`.
- Próximo paso sugerido: ejecutar M5 como diseño incremental de pivot/backfill para asociación múltiple.

---

## Estado global

### Documentación
- estado: `in_progress`
- notas: la estructura operativa nueva ya tiene roles, precedencia y prompt lanzador estándar; el daily 2026-04-24 consolida que `AnticipoCertificado` es la entidad de certificado y que no se crea una entidad nueva `certificados`. `docs/05-motor-de-conversacion.md` documenta la regla de bienvenida + menú al crear conversación. `docs/12` y `docs/07` documentan el plan de storage local real. Sigue pendiente sincronizar por completo documentos que describen el anticipo como parcial.

### Motor de conversación
- estado: `in_progress`
- notas: el motor ya tiene una capa común de interacción (`ConversationInteractionService`), lookup/alta por canal y una entrada interna de prueba sin depender de WhatsApp. Desde M2, toda conversación nueva responde bienvenida + menú y no interpreta el primer mensaje como selección de flujo.

### Flujos
- aviso: `in_progress`
- anticipo: `in_progress`
- notas: existe contradicción documental entre `docs/05-motor-de-conversacion.md` y `docs/diagrams/README.md` sobre el alcance real del anticipo; la decisión vigente es tomar `AnticipoCertificado` como entidad actual de certificado y no crear una entidad nueva.

### Modelo de datos
- estado: `in_progress`
- notas: se diseñó el corte de estados: agregar `avisos.estado`, cambiar default de `anticipos_certificado.estado` a `inicial`, persistir estado explícito desde servicios y agregar historial de estados como segundo corte. Falta implementar migraciones y decidir la estrategia de asociación de `AnticipoCertificado` con N avisos.

### Testing
- estado: `in_progress`
- notas: la suite completa pasó en M2 (`115 passed`, `384 assertions`).

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
- 2026-04-24 03:18 -03

### Plan diario usado
- `plan_dev/daily/2026-04-24.md`

### Milestone trabajado
- `M4 - Diseñar estados de avisos y anticipos`

### Resultado
- `done`

### Resumen corto
- se documentó el plan incremental para estados de negocio, con `inicial` como default, historial futuro de cambios y regla provisional de elegibilidad de avisos para anticipo.

---

## Cambios realizados
- archivos tocados: `docs/12-decisiones-tecnicas.md`, `docs/04-modelo-de-datos.md`, `docs/08-validaciones-y-reglas.md`, `plan_dev/daily/2026-04-24.md`, `plan_dev/STATUS.md`
- resumen técnico: se definió el plan de migraciones y servicios para estados de avisos y anticipos, incluyendo historial y elegibilidad provisional para anticipo.
- documentación actualizada: sí, documentación técnica de modelo, validaciones, decisiones y seguimiento operativo
- diagramas actualizados: no aplica para este recorte; no cambió estructura de flujo ni DB.

---

## Validaciones

### Automáticas
- tests corridos: no aplica
- resultado: cambio documental/devex sin impacto runtime
- otros checks: `git diff --check`
- resultado: sin errores de whitespace

### Manuales sugeridas
- revisar con negocio si `observado` debe seguir permitiendo carga de anticipo o debe bloquearse
- revisar si el historial de estados debe implementarse antes o junto con el primer admin

---

## Bloqueos actuales
- el estado documental del flujo de anticipo no está alineado entre todos los documentos; M1 deja decisión vigente, pero falta sincronización completa de `docs/05-motor-de-conversacion.md`
- falta definir si la asociación múltiple de anticipos a avisos será manual por operador o automática por reglas

---

## Decisiones humanas pendientes
- definir matriz mínima de permisos para `auditor` y `director`
- definir regla operativa de asociación múltiple entre `AnticipoCertificado` y avisos
- confirmar si avisos `observado` deben poder recibir anticipo o si pasan a ser bloqueantes

---

## Próximo milestone recomendado
- ejecutar `M5` de `plan_dev/daily/2026-04-24.md`: diseñar relación de `AnticipoCertificado` con múltiples avisos

---

## Referencia breve a backlog
- `LOG-001`: definir política de datos sensibles en logs quedó registrado como pendiente importante.
