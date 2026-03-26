# Master Plan

## Objetivo

Este documento es la fuente de verdad del roadmap general del proyecto Medicina Laboral WhatsApp MVP.

Debe responder:
- qué etapas existen
- en qué orden conviene ejecutarlas
- qué criterio de aceptación tiene cada bloque grande
- qué principios de trabajo siguen los agentes y desarrolladores

No debe usarse como bitácora diaria ni como lista fina de tareas del día.

---

## Principios del proyecto

- avanzar de lo estructural a lo funcional
- separar conversación de entidades de negocio
- evitar hardcodear lógica, mensajes y parámetros
- priorizar trazabilidad desde el comienzo
- mantener documentación viva
- incorporar tests de forma incremental y obligatoria en cambios relevantes
- mantener diagramas como código alineados con la implementación real

---

## Política de ejecución con agentes

Los agentes deben trabajar sobre:
1. `AGENTS.md`
2. este archivo (`MASTER_PLAN.md`)
3. `plan_dev/STATUS.md`
4. el archivo diario correspondiente en `plan_dev/daily/`

Los agentes deben ejecutar solo el primer milestone pendiente del plan diario, salvo que el plan diario indique otra cosa explícitamente.

## Relación con el resto de `plan_dev/`

- `MASTER_PLAN.md` ordena etapas y prioridades de mediano plazo.
- `STATUS.md` consolida el estado vigente del proyecto y la última ejecución.
- `daily/*.md` traduce el roadmap a milestones operativos del día.
- `BACKLOG.md` evita desvíos y conserva hallazgos fuera de alcance.

Los archivos fechados previos en `plan_dev/` deben considerarse históricos o insumos de transición. No reemplazan esta estructura salvo que un plan diario o `STATUS.md` indique lo contrario de forma explícita.

---

## Política general de stop/fix

Estas reglas aplican a todos los planes diarios.

Un agente debe **frenar** si:
- falla una validación obligatoria y no puede corregirla de forma segura;
- hay una ambigüedad funcional o de negocio relevante;
- hay más de una decisión de diseño razonable y no existe definición previa;
- falta acceso a un sistema externo, credencial o contrato técnico;
- el cambio impacta demasiadas capas y deja de ser acotado;
- el milestone ya no puede cerrarse sin abrir una refactorización mayor.

Un agente puede **continuar automáticamente** si:
- el milestone está bien definido;
- el cambio es acotado;
- las convenciones ya están documentadas;
- las validaciones obligatorias pasan;
- el siguiente paso no depende de una decisión humana nueva.

---

## Roadmap general

### Etapa 1
Base documental y decisiones

### Etapa 2
Base del motor de conversación

### Etapa 3
Centralización de textos y parámetros

### Etapa 4
Estructura del flujo por pasos

### Etapa 5
Menú principal y navegación base

### Etapa 6
Identificación común

### Etapa 7
Flujo de aviso de ausencia

### Etapa 8
Flujo de anticipo de certificado

### Etapa 8.5
Base de testing y cobertura inicial

### Etapa 9
Validaciones, intentos y errores

### Etapa 10
Inactividad y automatismos

### Etapa 11
Mensajes finales y templates

### Etapa 12
Integraciones futuras y endurecimiento

---

## Estado esperado al finalizar el roadmap base

El proyecto debería contar con:
- motor conversacional trazable
- flujos principales funcionales
- validaciones consistentes
- automatismos básicos de inactividad
- mensajes y templates ordenados
- tests sobre piezas relevantes
- puntos de extensión claros para integraciones futuras

---

## Documentación viva obligatoria

Cuando un cambio impacta de forma relevante en:
- flujo conversacional
- arquitectura
- modelo de datos
- testing
- integraciones
- diagramas

deben actualizarse los documentos correspondientes en `docs/` y, si aplica, `docs/diagrams/`.

---

## Criterio de uso

- Este archivo cambia poco.
- Sirve para ordenar el proyecto a mediano plazo.
- El detalle operativo del día vive en `plan_dev/daily/YYYY-MM-DD.md`.
- El estado actual consolidado vive en `plan_dev/STATUS.md`.
- Pendientes no priorizados viven en `plan_dev/BACKLOG.md`.
- Los documentos fechados previos conservan contexto histórico, pero no son la fuente de verdad activa del trabajo.
