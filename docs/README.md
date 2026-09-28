# Documentación

## Objetivo

Este directorio reúne la documentación funcional y técnica viva del proyecto.

Debe servir como base común para:

- desarrollo
- revisión técnica
- prompts a Codex u otros agentes
- evolución incremental del sistema

## Trabajo con agentes

Entrada selectiva: [índice de memoria](ai-memory/INDEX.md).
Procedimiento: [workflow multiagente](../plan_dev/MULTI_AGENT_WORKFLOW.md).
El índice siguiente es un mapa de documentación, no una lista a cargar completa
por cada worker.

## Orden recomendado de lectura

- `03-arquitectura.md`
- `04-modelo-de-datos.md`
- `05-motor-de-conversacion.md`
- `06-flujo-aviso-ausencia.md`
- `07-flujo-anticipo-certificado.md`
- `08-validaciones-y-reglas.md`
- `09-scheduler-e-inactividad.md`
- `10-mensajes-y-templates.md`
- `11-testing-y-criterios.md`
- `12-decisiones-tecnicas.md`
- `13-operacion-y-soporte.md`
- `14-certificados-problematica-y-plan-incremental.md`: problemática, decisiones y entregas incrementales de certificados.
- `15-inventario-tablas-maestras.md`: candidatos para CAT-001; selección de migración pendiente.
- `backoffice/README.md`

## Diagramas como código

La documentación visual del proyecto vive en `docs/diagrams/`.

Convención adoptada:

- Mermaid para flujos conversacionales
- PlantUML para diagramas de clases
- DBML para esquema de base de datos

Referencia principal:

- `docs/diagrams/README.md`

Estos diagramas forman parte de la documentación viva y deben considerarse cuando cambian flujos o estructura relevante.
