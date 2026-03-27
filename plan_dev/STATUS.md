# Status

## Objetivo

Este archivo consolida el estado actual del proyecto y deja trazada la última ejecución relevante.

No debe reemplazar:
- el roadmap de `plan_dev/MASTER_PLAN.md`
- el detalle operativo de `plan_dev/daily/`
- el backlog de `plan_dev/BACKLOG.md`

---

## Fecha de última actualización
2026-03-27 12:54 -03

## Resumen ejecutivo
- Estado general del proyecto: el `daily` del 2026-03-26 quedó ejecutado de punta a punta desde `M1` hasta `M8`, dejando una base mínima multicanal operativa y validada.
- Último bloque completado: implementación y validación de `M3` a `M8`, incluyendo la consola local, el endpoint interno, el desacople del sender y una mejora acotada de trazabilidad.
- Milestone actual: no quedan milestones pendientes en `plan_dev/daily/2026-03-26.md`.
- Próximo paso sugerido: abrir un nuevo `daily` para el siguiente bloque del roadmap, usando la consola local como herramienta de prueba manual y aplicando la nueva regla de commits chicos por corte claro.

---

## Estado global

### Documentación
- estado: `in_progress`
- notas: la estructura operativa nueva ya tiene roles, precedencia y prompt lanzador estándar; `docs/05-motor-de-conversacion.md` documenta el desacople por canal. Sigue pendiente sincronizar documentos técnicos que hoy se contradicen sobre el estado real del flujo de anticipo.

### Motor de conversación
- estado: `in_progress`
- notas: el motor ya tiene una capa común de interacción (`ConversationInteractionService`), lookup/alta por canal y una entrada interna de prueba sin depender de WhatsApp.

### Flujos
- aviso: `in_progress`
- anticipo: `in_progress`
- notas: existe contradicción documental entre `docs/05-motor-de-conversacion.md` y `docs/diagrams/README.md` sobre el alcance real del anticipo; requiere validación humana o técnica antes de seguir tomando decisiones sobre ese flujo.

### Testing
- estado: `in_progress`
- notas: la suite completa pasó luego del refactor multicanal y de la consola local (`114 passed`).

### Inactividad / scheduler
- estado: `in_progress`
- notas: el roadmap lo contempla como etapa posterior; no hubo cambios en este milestone.

### Integraciones futuras
- estado: `pending`
- notas: siguen planteadas como desacopladas y futuras.

---

## Última ejecución del agente

### Fecha/hora
- 2026-03-27 12:54 -03

### Plan diario usado
- `plan_dev/daily/2026-03-26.md`

### Milestone trabajado
- ajuste de reglas operativas para estrategia de commits

### Resultado
- `done`

### Resumen corto
- se formalizó en `AGENTS.md` la política de commits chicos, separando `refactor`, `feat`, `test` y `docs` cuando el corte sea claro, y se dejó alineado el prompt lanzador para recordar esa regla.

---

## Cambios realizados
- archivos tocados: `AGENTS.md`, `plan_dev/RUNBOOK_PROMPT.md`, `plan_dev/STATUS.md`
- resumen técnico: se agregó una regla explícita para preferir commits pequeños, con separación por tipo cuando el corte sea claro y con referencia obligatoria a `daily` y `milestone` en el cuerpo del commit.
- documentación actualizada: sí, seguimiento operativo del daily y estado consolidado
- diagramas actualizados: no aplica para este recorte

---

## Validaciones

### Automáticas
- tests corridos: no aplica
- resultado: cambio documental/operativo
- otros checks: revisión de consistencia entre `AGENTS.md`, `plan_dev/RUNBOOK_PROMPT.md` y la rutina operativa vigente
- resultado: la regla de commits quedó alineada con la ejecución diaria y el prompt lanzador

### Manuales sugeridas
- en el próximo daily, aplicar la convención nueva sobre un milestone real con más de un corte natural
- decidir si el alcance real del flujo de anticipo es el de `docs/05-motor-de-conversacion.md` o el de `docs/diagrams/README.md`

---

## Bloqueos actuales
- el estado documental del flujo de anticipo no está alineado entre todos los documentos

---

## Decisiones humanas pendientes
- validar cuál es la referencia correcta sobre el estado implementado del flujo de anticipo

---

## Próximo milestone recomendado
- abrir un nuevo `daily` alineado con `MASTER_PLAN.md` y usar `/internal/chat` como canal interno de prueba para el siguiente bloque de trabajo

---

## Referencia breve a backlog
- si aparecen mejoras fuera del alcance durante la próxima ejecución, registrarlas en `plan_dev/BACKLOG.md` y dejar aquí solo una mención resumida
