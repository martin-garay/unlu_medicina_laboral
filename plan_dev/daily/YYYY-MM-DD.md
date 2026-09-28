# Daily YYYY-MM-DD — <objetivo>

## Alcance y orden autorizado

- Referencias: [AGENTS](../../AGENTS.md), [MASTER_PLAN](../MASTER_PLAN.md),
  [STATUS](../STATUS.md), [workflow](../MULTI_AGENT_WORKFLOW.md).
- Continuidad: <daily/milestone anterior; bloqueos que se conservan>.
- Orden: <IDs>. Ejecutar sólo el primer pendiente. Excepciones: <NONE o autorización>.
- No copiar backlog aquí; enlazar los items promovidos explícitamente.

## M1 — <nombre>

Estado: `pending`. Fase: `READY` (o `BLOCKED` si faltan requisitos).

### Goal / objetivo

<Resultado verificable>

### Scope / fuera de alcance

<Archivos/módulos y qué no se implementa>

### Dependencies / contratos compartidos

<Prerequisitos, contratos existentes o contrato ID/revisión/base: campos, interfaces,
rutas, permisos, errores, esquema, estados/eventos según aplique. NONE si no aplica.>

### Tasks / estrategia de ejecución

Modo: único agente / secuencial / paralelo. Motivo: <independencia o acoplamiento>.
Para un cambio simple bastan scope, aceptación y tests; omitir tabla/fichas vacías.
Para delegación completar la ficha de [ownership](../MULTI_AGENT_WORKFLOW.md#asignación-mínima-para-tareas-delegadas).

| Task | Owner | Allowed files | Branch / Worktree / Base | Depends on | Estado / Fase |
| --- | --- | --- | --- | --- | --- |
| <ID> | <owner> | <rutas> | <ruta y SHA o NONE> | <IDs> | pending / READY |

Grafo: `<C1 -> (T1 || T2) -> Review -> Integration>`; clasificar dependencias
PARALLELIZABLE / SEQUENTIAL / BLOCKED. Reservar archivos compartidos a un owner.

### Acceptance criteria / entregable

- <Comportamiento o resultado documental observable>

### Validación automática obligatoria / Regression tests

- <Comandos + entorno exacto; diferenciar pruebas de workers y del HEAD integrado>
- `git diff --check`.

### Validación manual sugerida

- <Recorrido; marcar explícitamente si es obligatoria para este corte>

### Risks / condiciones de stop

- <Riesgos, ambigüedades y condiciones de bloqueo específicas>
- Si falla validación obligatoria, no avanzar. ¿Saltar bloqueo?: no, salvo excepción autorizada arriba.

### Review / Integration

<Reviewer, base/HEAD, findings y resolución. Plan de integración y regresión conjunta.
Para corte trivial indicar revisión propia; si no aplica integración entre ramas, declararlo.>

### Definition of done

Tasks finalizadas; integración y tests/regresiones del conjunto correctos;
BLOCKER/HIGH resueltos; docs/diagramas y daily/STATUS actualizados;
memoria evaluada; commits trazables y Git limpio, sin alterar trabajo ajeno.

### Resultado

<Fecha/hora, estado/fase, evidencia, commits, limitaciones y próximo paso.
Entregas TASK RESULT sólo para tareas delegadas. Memory: IGNORE / UPDATE_EXISTING / ADD.>

## Cierre del día

Usar MILESTONE SUMMARY del workflow. No avanzar otro milestone sin autorización
cuando el alcance es uno solo. Replicar el bloque M1 únicamente si hay más trabajo
aprobado; estados/fases se mantienen aquí y snapshot consolidado en STATUS.
