# Trabajo multiagente sobre Milestones y Daily Plans

Fuente de reglas obligatorias: [AGENTS.md](../AGENTS.md). Este documento amplía
el procedimiento; no crea otro backlog, otro plan ni un sistema de tickets.

## Arranque y elección del modo

El Lead lee las referencias operativas en el orden de AGENTS, identifica el
primer milestone pendiente del daily y consulta [el índice de memoria](../docs/ai-memory/INDEX.md).
Lee sólo la memoria y fuentes relacionadas. No ejecuta un milestone bloqueado
ni lo salta sin autorización explícita reflejada en el daily.
Si falta el daily, lo declara y sólo lo crea a partir del alcance autorizado;
no convierte por su cuenta un backlog o un ejemplo en trabajo aprobado.

Antes de editar registra en el milestone: objetivo, dependencias, contratos,
archivos afectados, ownership y modo elegido:

| Modo | Cuándo | Registro mínimo |
| --- | --- | --- |
| Único agente | Cambio pequeño/cohesivo, sin paralelismo útil | Scope, aceptación y tests; sin tabla de workers ni branches adicionales |
| Secuencial | Contrato incierto, archivos compartidos o dependencia fuerte | Orden de tareas y handoffs |
| Paralelo | Unidades independientes con contratos estables y validación posible | Tabla de tareas, grafo y ownership exclusivos |

Máximo inicial recomendado: **Lead + hasta 3 workers concurrentes**. No ocupar
slots por obligación; reviewer/test/integration también cuentan si están activos.
El Lead autoriza delegaciones; workers no subdelegan ni amplían alcance solos.
Un rol es una responsabilidad, no exige otro proceso. Tareas triviales siguen
siendo rápidas con un solo agente y revisión propia explícitamente identificada.

## Roles

- **Lead / Orchestrator:** responsable técnico; analiza dependencias/conflictos,
  fija contratos, asigna trabajo, comunica descubrimientos a afectados, decide
  sobre findings y memoria, integra, valida y actualiza daily/STATUS/docs.
- **Implementation Worker:** implementa sólo lo asignado, respeta contratos,
  ejecuta pruebas relacionadas y entrega resultado estructurado. Solicita al Lead
  reasignación antes de tocar archivos ajenos; no escribe STATUS/memoria global.
- **Test Worker:** diseña pruebas desde contratos y criterios observables; no
  necesita el detalle interno de la implementación. Puede escribir tests en
  paralelo pero no declarar resultado integrado hasta probar commits compatibles.
- **Reviewer:** examina un diff/base/HEAD exactos con contexto mínimo propio;
  no es autor de esos cambios. Primero informa findings, sin editar código.
- **Integration Agent / Lead:** integra commits en orden, resuelve dependencias,
  comprueba contratos/migraciones/UI/backend y ejecuta regresiones del conjunto.
  La integración es una fase explícita incluso si la realiza el Lead.

## Un solo tablero en el daily

Conservar los estados legacy y agregar `Fase` donde haga falta. No reescribir
los dailies históricos ni crear un segundo estado consolidado.

| Fase | Estado compatible / significado |
| --- | --- |
| BACKLOG | Vive en BACKLOG; no es una tarea habilitada del día |
| READY | `pending`, scope/contratos/dependencias suficientes para iniciar |
| IN PROGRESS | `in_progress`, trabajo activo secuencial o de un solo agente |
| PARALLEL | `in_progress`, conjunto activo de tareas independientes |
| BLOCKED | `blocked`, razón y condición de desbloqueo explícitas |
| REVIEW | `needs_review`, esperando findings o decisión humana identificada |
| INTEGRATION | `in_progress`, integración/regresión aún no concluida |
| DONE | `done`, todos los gates del cierre satisfechos |

La fase distingue dónde está el trabajo; no habilita saltar un bloqueo.
`needs_review` no significa siempre aprobación humana: indicar quién debe revisar
y qué falta. Si una validación obligatoria no puede correr, el Lead no marca done.

## Contratos y dependencias antes del paralelismo

El contrato vive **en el milestone del daily** o referencia la especificación
canónica existente; no copiarla en cada tarea. Marcar ID/revisión y commit base.
Registrar sólo lo aplicable: rutas/métodos, entradas/salidas, campos/tipos,
nullabilidad, errores, permisos, modelos/FKs/migraciones, estados, eventos,
interfaces, traducciones y criterios de aceptación. Explicitar fuera de alcance.

Clasificar cada dependencia `PARALLELIZABLE`, `SEQUENTIAL` o `BLOCKED` y escribir
un grafo corto, por ejemplo `C1 -> (T1 || T2) -> R1 -> I1`. No paralelizar sólo
porque hay varias tareas. En este Laravel, backend y Filament pueden tocar el
mismo Resource, modelo, provider, config o traducción: definir dueño antes.

Ningún worker modifica un contrato unilateralmente. Reporta el cambio necesario;
el Lead detiene los consumidores afectados, revisa contrato, comunica nueva
revisión/base y revalida los resultados ya hechos. Hallazgos comunes se comparten
una vez con referencias; evitar investigaciones duplicadas.

## Asignación mínima para tareas delegadas

Copiar en el daily sólo cuando hay delegación; un documento por tarea es opcional
para un corte grande, nunca un requisito para cambios simples.

```markdown
## TASK-ID
Goal:
Owner: <rol/agente>
Scope:
Allowed files / modules: <rutas concretas; un único escritor por archivo>
Dependencies: <IDs + PARALLELIZABLE / SEQUENTIAL / BLOCKED>
Contracts: <ID/revisión/enlace>
Acceptance criteria:
Tests: <comandos, entorno, resultado esperado>
Do not touch:
Expected output: TASK RESULT + commits pequeños
Base commit:
Branch / Worktree: <ruta absoluta; NONE si lectura o secuencial>
Memory to read: <INDEX + sólo las entradas aplicables>
```

Enviar al worker esa ficha, AGENTS aplicables y referencias necesarias. Evitar
heredar automáticamente toda la conversación, daily o documentación. El Lead ya
hizo la lectura operativa global; el worker lee su ficha/sección del milestone,
contratos y reglas de sus rutas, sin relevar otra vez todo el repositorio.

Los workers reportan dependencias descubiertas y riesgos de inmediato; no esperan
al cierre para informar un bloqueo. Cambios en providers, lockfiles, esquema de
pruebas compartido, traducciones, configuración o documentación global tienen un
único dueño. Si se solapan: reasignar o serializar. Un merge posterior no sustituye
el ownership. Excepciones requieren plan explícito de integración del Lead.

## Git y aislamiento

Antes: `git status --short`, `git branch --show-current`, `git worktree list`.
Identificar trabajo ajeno y no incluirlo, moverlo, borrarlo ni hacer stash/reset
para "limpiar". Registrar base SHA y destino de integración.

Con implementación paralela real, cada worker usa **branch y worktree distintos**.
Nombres: `feature/<task-id>-<descripcion>`, `fix/...`, `refactor/...`, `test/...`;
para documentación `docs/...`. El Lead puede usar `integration/<milestone>`.
No crear branches adicionales para lectura/review o tareas triviales.

Ejemplo operativo Git (sustituir IDs; ejecutar sólo al asignar trabajo real):

```bash
base_sha=$(git rev-parse HEAD)
agent_worktree_root=$(mktemp -d /tmp/medicina-worktrees.XXXXXX)
git worktree add -b feature/ml-042-catalogo "$agent_worktree_root/ml-042" "$base_sha"
git worktree add -b test/ml-043-catalogo "$agent_worktree_root/ml-043" "$base_sha"
git worktree add -b integration/ml-040 "$agent_worktree_root/integration" "$base_sha"
```

Registrar las rutas del comando en el daily y exigir que cada worker ejecute desde
su ruta y verifique `pwd`/rama antes de escribir. En herramientas que comparten
cwd, fijar `workdir` explícitamente; crear un agente no aísla el filesystem.

- Si no hay aislamiento de escritura disponible, **serializar la implementación**;
  pueden coexistir lectores. No trabajar en paralelo sobre el checkout principal.
- `.env`, `vendor/`, `bootstrap/`, `storage/` y herramientas ignoradas no se
  transfieren por Git. Preparar cada entorno explícitamente, sin copiar secretos
  ni compartir caches/directorios mutables entre workers.
- Worktrees no aíslan puertos, Docker, DB ni servicios externos. El Lead asigna
  entorno exclusivo o serializa pruebas con efectos. Ver [testing en memoria](../docs/ai-memory/testing.md).
- No usar `make test` en el checkout principal para validar código que sólo está
  en otro worktree. Registrar siempre SHA, ruta montada y entorno probados.

## Review e integración controlada

Para cambios paralelos o relevantes exigir revisión independiente antes del
cierre. El reviewer recibe objetivo, contrato, base/HEAD, diff y evidencia de
pruebas; no necesita el historial de razonamiento de los autores. Si no está
disponible, conservar `needs_review`; no presentar autorrevisión como independiente.
Para un cambio trivial, el Lead puede registrar revisión propia y su justificación.

Formato de finding: `ID | severidad | archivo:línea | problema/impacto |
reproducción o evidencia | corrección sugerida`. Severidades:

- **BLOCKER:** impide integrar o verificar el alcance de forma segura.
- **HIGH:** defecto serio, regresión, seguridad o contrato incumplido.
- **MEDIUM / LOW:** impacto acotado, mejora concreta.
- **SUGGESTION:** recomendación opcional.

El Lead asigna correcciones; reviewer no edita de inmediato. BLOCKER/HIGH deben
resolverse y revisarse de nuevo antes de done. Para los demás registrar corrección
o aceptación justificada y backlog cuando corresponda. "Sin findings" debe
identificar SHA/diff revisado y limitaciones; no implica ejecución de tests.

Integración, siempre secuencial:

1. Verificar entregas, commits, scope, dependencias y resultados. Las tareas
   autónomas pueden estar done aunque el milestone siga en integración.
2. En la rama de integración, aplicar commits explícitos por orden de dependencia
   (`git cherry-pick <sha>` o merge revisado, una estrategia por corte). Nunca
   integrar automáticamente porque un worker terminó; no hacer push implícito.
3. Resolver conflictos entendiendo el contrato; no usar aceptación global de
   "ours/theirs". Cambios semánticos/conflictos vuelven a review.
4. Ejecutar pruebas conjuntas sobre HEAD integrado. Una modificación posterior
   relevante requiere repetir los checks afectados. Revisar compatibilidad de
   migraciones y backend/Filament cuando aplique; tests individuales no bastan.
5. Con evidencia y review conformes, incorporar al destino acordado (por ejemplo
   fast-forward si su historia lo permite). Si el destino cambió, reintegrar y
   revalidar; no forzar historia. Si hay PR requerida, no marcar integración
   finalizada antes de su merge autorizado; dejar needs_review.
6. Archivar resultado compacto en daily/STATUS, commits trazables y Git limpio.
   No limpiar trabajo ajeno para cumplir el gate: aislar o informar el impedimento.
7. Retirar worktrees sólo tras confirmar commits conservados y árbol sin cambios;
   usar `git worktree remove <ruta>` sin `--force`. No borrar ramas ajenas.

Deploy/publicación, secretos y cambios remotos siguen sujetos al alcance y
procedimientos existentes bajo `deploy/`; integrar código no autoriza desplegar.

## Entrega obligatoria de worker

```text
TASK RESULT
Task:
Status: done / blocked / needs_review
Implementation:
- <resumen>
Files changed:
-
Tests:
- <comando + SHA/entorno>
Test result:
Decisions:
Dependencies discovered: NONE / ...
Contract changes: NONE / ...
Potential conflicts: NONE / ...
Risks:
-
Memory candidates:
-
Commit: <SHA(s), o NONE y motivo>
```

## Cierre y memoria

Antes de DONE: todas las tareas finalizadas; integración y regresiones realizadas;
BLOCKER/HIGH resueltos; validación manual obligatoria si fue definida; daily,
STATUS y documentación/diagramas actualizados; commits y árboles de trabajo
limpios, sin alterar trabajo ajeno. No esperar pruebas remotas no requeridas para
el corte, pero declarar el límite. No declarar validaciones no ejecutadas.

El Lead pregunta: **¿qué aprendimos que evitaría investigar otra vez?** Clasifica
cada memory candidate como `IGNORE`, `UPDATE_EXISTING` o `ADD`. Si nada aporta,
no modifica memoria. Sólo el Lead publica memoria consolidada; ver su [política](../docs/ai-memory/README.md).

```text
MILESTONE SUMMARY
Implemented:
Tests:
Architecture changes:
Decisions:
Known limitations:
Memory updated: NONE / <archivos y motivo>
Follow-up:
Commits:
```

## Ejemplo: CAT-001, sólo ilustrativo; NO autoriza implementación

CAT-001 sigue en backlog, con selección pendiente. No inventar un endpoint ni
administración aprobada. Suponer únicamente para el ejemplo que se aprobara C1:

```text
EX-C0 selección/semántica de sedes (BLOCKED hasta decisión del usuario)
  -> EX-C1 contrato y esquema (SEQUENTIAL, Lead)
     -> EX-B backend/DB || EX-T tests desde contrato (PARALLELIZABLE)
        -> EX-R revisión independiente (SEQUENTIAL)
           -> EX-I integración/regresión (SEQUENTIAL)
```

| Tarea | Owner / archivos permitidos | Depende de | Fuera de scope |
| --- | --- | --- | --- |
| EX-C1 | Lead: contrato en daily | EX-C0 | Implementar catálogos no seleccionados |
| EX-B | Backend/DB: migración nueva, modelo/servicio de sedes, validador/handler de sede y binding acordados | EX-C1 | UI administrativa, tests y memoria |
| EX-T | Test worker: nuevos tests de sedes en `tests/Unit/` y `tests/Feature/` | EX-C1; ejecución integrada espera EX-B | Runtime; `CreatesTestingSchema` compartido sin asignación expresa |
| EX-R | Reviewer: sólo lectura del diff EX-B + EX-T | EX-B, EX-T | Cambiar código |
| EX-I | Lead: integración y docs/diagrama/daily/STATUS | EX-R sin BLOCKER/HIGH | Deploy |

EX-C1 debe definir código/etiqueta/activo/orden, servicio consultado por menú y
validación, IDs y aliases estables, tratamiento de sesiones/históricos, schema/FKs
y backfill si aplica, errores/permisos y pruebas de aceptación. El contrato sigue
sin aprobarse: no se presume que esos campos sean la solución final.
No se crea worker frontend por obligación: aquí el chat Blade consume el menú
existente. Si luego se aprueba CRUD Filament, se asigna su Resource/permiso en un
corte separado o tras acordar el contrato y evitar archivos compartidos.

## Diagnóstico de adopción (2026-09-27)

- Un único `AGENTS.md` versionado; copias en releases locales ignorados no rigen
  el checkout. Buscar reglas de subdirectorios al asignar rutas en futuras sesiones.
- `MASTER_PLAN`, `STATUS`, `daily/`, `BACKLOG` y `RUNBOOK_PROMPT` ya organizan el
  trabajo. Se amplían plantilla y prompt; no se introduce gestor de tareas nuevo.
- Decisiones existentes en `docs/12-decisiones-tecnicas.md` y documentos de dominio;
  no se crea un directorio ADR competidor.
- Laravel 11/PHP, PostgreSQL y Docker; UI Blade/Filament. No hay SPA ni
  `package.json` versionado. Tests PHPUnit Unit/Feature, Makefile y scripts locales.
- No se encontraron pipelines CI/CD versionados; Ansible y sus checks ya existen
  bajo `deploy/`. No se instala un pipeline en este corte documental.
- Git usa commits pequeños con referencia daily/milestone; releases de testing
  usan tags. Se documentan worktrees, sin crear ramas o despliegues innecesarios.
- STATUS acumulaba actividad histórica y estados contradictorios. Se conserva
  [snapshot previo](archive/STATUS-2026-09-27-before-multi-agent.md) como historial
  y se deja un snapshot operativo corto. El archivo histórico no es lectura inicial.
