# Runbook Prompt

Lanzador único para Milestones + Daily Plans; el Lead elige el número de agentes.
No hace falta pedir "multiagente" en cada sesión ni copiar la documentación.

## Prompt para una ejecución

```text
Actuá como Lead de este repositorio siguiendo AGENTS.md y
plan_dev/MULTI_AGENT_WORKFLOW.md.

Daily: plan_dev/daily/YYYY-MM-DD.md
Ejecutá sólo su primer milestone pendiente; respetá bloqueos y alcance aprobado.
Leé las referencias operativas en orden, luego docs/ai-memory/INDEX.md y sólo
la memoria relevante. Si falta el daily o una definición, informalo sin inventar
alcance ni promover backlog por tu cuenta.

Antes de editar proponé un plan corto de archivos, dependencias y modo de trabajo:
agente único, secuencial o paralelo. Si delegás, fijá contratos y ownership primero,
con hasta 3 workers concurrentes y worktrees para implementación paralela.

Revisá, integrá y validá el conjunto; actualizá daily/STATUS y documentación.
Evaluá memory candidates sin duplicar información. Cerrá con commits pequeños
y MILESTONE SUMMARY, o dejá bloqueo/review pendiente con causa y próximo paso.
```

Reemplazar la fecha por el daily **acordado**, no necesariamente el día calendario.
Si no existe un plan para el trabajo nuevo, agregar al pedido objetivo, alcance,
fuera de alcance y prioridad respecto de pendientes para que el Lead lo prepare.
Una tarea simple no necesita workers ni branches extra.

## Próximo trabajo de producto después de MA-001

El workflow está listo; no hay un milestone nuevo de producto autorizado por su
adopción. M5 de [2026-09-14](daily/2026-09-14.md) sigue bloqueado por decisiones;
CAT-001 sigue en backlog con selección pendiente. Indicar qué resolver/priorizar
antes de pedir implementación; MA-001 no es autorización para saltar esos bloqueos.
