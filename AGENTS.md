# AGENTS.md

## Proyecto y alcance de estas reglas

Medicina Laboral UNLu: MVP Laravel + PostgreSQL + Docker; WhatsApp Cloud API,
aviso de ausencia y anticipo de certificado. Mantener componentes pequeños,
trazabilidad completa y extensibilidad por pasos.

Estas reglas rigen el repo. Buscar también `AGENTS.md` en los subdirectorios
asignados y respetar su scope; no tomar copias de releases/vendor como reglas del
checkout. Las instrucciones explícitas del usuario prevalecen sobre estas guías.

## Arranque y fuentes de verdad

El Lead lee, en este orden, antes de una tarea relevante:

1. Este `AGENTS.md` y reglas más específicas aplicables.
2. [MASTER_PLAN](plan_dev/MASTER_PLAN.md): roadmap y orden general.
3. [STATUS](plan_dev/STATUS.md): snapshot consolidado y última ejecución.
4. Daily correspondiente en `plan_dev/daily/YYYY-MM-DD.md`, o el indicado.
5. [INDEX de memoria](docs/ai-memory/INDEX.md); sólo las entradas pertinentes.

Si falta una referencia obligatoria, declararlo; no inventar el plan activo.
Precedencia operativa: AGENTS → daily → STATUS → MASTER_PLAN. Los planes fechados
legacy y `archive/` son históricos salvo referencia explícita. No usar charlas
anteriores como única fuente de verdad cuando hay archivos actuales.

| Fuente | Autoridad |
| --- | --- |
| Código / migraciones | Comportamiento y esquema implementados |
| Tests | Comportamiento esperado verificable; no prueban lo no ejecutado |
| Daily / milestone | Alcance, contratos, tareas y orden del trabajo autorizado |
| STATUS | Estado consolidado actual y resultado de última ejecución |
| BACKLOG | Hallazgos y trabajo no priorizado |
| `docs/12-decisiones-tecnicas.md` y docs de dominio | Decisiones con estado explícito, no implementación presumida |
| AI memory | Mapa y conocimiento reusable; nunca reemplaza código/tests/docs canónicas |

Antes de tocar código, consultar `README.md`, `docs/README.md` y
`docs/05-motor-de-conversacion.md`; leer las secciones pertinentes de los flujos
06/07, reglas 08, scheduler 09 y `docs/diagrams/README.md` según el impacto.
Usar búsqueda y lectura dirigida. No releer material sin cambios dentro de la
misma sesión. Workers reciben la ficha/contrato y fuentes de su dominio: el Lead
centraliza la lectura operativa global, no obliga a repetir todo el relevamiento.

## Ejecución por milestone

Trabajar sólo el **primer milestone pendiente**. No saltar uno bloqueado salvo
priorización explícita del usuario registrada en el daily.
Secuencia: analizar → plan corto de archivos → contratos/ownership/modo →
implementar → review → integrar/validar → actualizar daily/STATUS → commit/cierre.
El [workflow](plan_dev/MULTI_AGENT_WORKFLOW.md) define roles, estados, fichas,
contratos, handoffs, worktrees, findings y gates. La [plantilla daily](plan_dev/daily/YYYY-MM-DD.md)
reúne objetivo, scope/fuera de scope, dependencias, aceptación, regresión y riesgos.
Usar [RUNBOOK_PROMPT](plan_dev/RUNBOOK_PROMPT.md) como lanzador.

## Paralelismo y responsabilidad

- El agente principal es Lead técnico. Decide **único agente / secuencial /
  paralelo** según dependencias reales; no delegar tareas triviales.
- Máximo inicial recomendado: 3 workers concurrentes, además del Lead; no hay
  obligación de ocuparlos. Workers no subdelegan sin decisión del Lead.
- Antes de paralelizar: contratos compartidos estables, grafo de dependencias,
  acceptance criteria, archivos permitidos/prohibidos, owner y entorno por tarea.
- Un escritor por archivo. Providers, lockfiles, configuración, traducciones,
  migraciones, esquema de tests y docs globales también tienen dueño explícito.
- Implementación paralela: branch/worktree propios; verificar cwd/rama. Si el
  entorno no permite aislamiento, serializar; lectores pueden trabajar en paralelo.
- Worktrees no aíslan DB, puertos, Docker ni hosts remotos. Asignar operador único
  o entornos exclusivos. No modificar trabajo de otro agente ni sus recursos.
- Reviewer independiente para cambios paralelos/relevantes: primero findings,
  sin editar. Resolver BLOCKER/HIGH antes del cierre. Cambios triviales admiten
  revisión propia declarada; no llamarla independiente.
- Sólo Lead/Integration integra commits, valida el conjunto y publica STATUS,
  daily y memoria. Los workers entregan `TASK RESULT` y memory candidates.

## Reglas de diseño y modelado

- Textos en `lang/es/*.php`; mensajes largos en templates Blade; parámetros y
  catálogos en `config/*.php` mientras no se apruebe otra fuente explícitamente.
- Controllers livianos; negocio en servicios/handlers. Evitar switches gigantes.
- Cada paso define dato esperado, validación, respuesta de éxito/error, intentos
  y transición. Separar normalización, validación, persistencia, respuesta y
  materialización de negocio.
- Conversación = unidad técnica de trazabilidad/sesión viva, separada del aviso
  y del anticipo. El anticipo requiere aviso previo; una conversación no siempre
  produce un aviso. Soportar estado/flujo/intentos/timestamps/timeout y cierres.
- Persistir entradas/salidas asociadas a conversación: dirección, tipo, contenido,
  payload, validez/motivo y paso cuando sea posible. Registrar eventos de estado,
  errores, inactividad, cancelación y creación de entidades.
- No borrar conversaciones/mensajes cancelados, expirados o fallidos. No asumir
  que una conversación cancelada puede reutilizarse ni que toda entrada es texto.
- Automatismos de inactividad con Laravel Scheduler. Integraciones externas e
  identificación detrás de interfaces/servicios mockeables. Catálogos y textos
  pueden evolucionar: conservar trazabilidad histórica.

## Deploy

- Todo documento, inventory, playbook, rol, template, prueba o código específico
  de despliegue vive en `deploy/`; fuentes canónicas: `deploy/README.md` y
  `deploy/docs/`. `plan_dev/` sólo planificación operativa y estado.
- Cada tecnología tiene rol estándar en `deploy/provisioning/roles/` con README,
  defaults, soporte por versión y tests. Inventory elige proveedor/versión por
  separado; no perfiles monolíticos sin dependencia técnica real.
- Agregar soporte dentro de cada tecnología; no alterar una versión validada
  para adaptar otra. Playbooks generales sólo orquestan: sin paquetes, rutas de
  servicio ni lógica específica de Debian/Ubuntu/Apache/PHP/PostgreSQL.
- Integrar un milestone no autoriza despliegue ni publicación implícitos.

## Stop/fix y validaciones

Cada milestone define validación automática obligatoria, manual sugerida y stops.
Cambios relevantes incluyen tests adecuados al comportamiento en el mismo corte.
No avanzar si falla un check obligatorio. Un intento razonable de corrección
menor; no encadenar fixes indefinidos sin actualizar estado.

`blocked`: ambigüedad funcional/técnica relevante sin definición, credencial o
contrato faltante, dependencia externa inaccesible, refactor mayor fuera de scope
o imposibilidad de validación mínima segura. No considerar una elección rutinaria
compatible con los contratos como bloqueo.
`needs_review`: falta revisión independiente requerida o decisión/aceptación humana
identificada. Declarar exactamente qué falta; no pedir aprobaciones redundantes.
`done`: tareas e integración terminadas, regresiones obligatorias correctas,
BLOCKER/HIGH resueltos, docs/daily/STATUS actualizados, memoria evaluada y Git limpio.
Si falta entorno, declarar validaciones no ejecutadas; no afirmar validación total.

## Git, documentación y cierre

- Revisar estado inicial; nunca borrar, revertir ni incluir cambios ajenos para
  cumplir el cierre. Si impiden Git limpio, aislar o dejarlo explícitamente pendiente.
- Cada milestone cerrado tiene al menos un commit pequeño y trazable salvo pedido
  de no commitear o blocked/needs_review sin cambios integrables. Separar cortes
  `refactor`, `feat`, `test`, `docs` cuando sea natural; tests necesarios pueden
  acompañar implementación. No commits gigantes ni cambios fuera de alcance.
- Título `<tipo>: <resumen>`; cuerpo con `Daily: <ruta>`, `Milestone: <ID>` y
  `Resumen: <objetivo>`. Actualizar daily/STATUS antes o dentro del último commit.
- No merges automáticos ni push implícito. Integrar en orden y validar HEAD conjunto,
  no asumir que tests aislados prueban el milestone. Detalle Git en workflow.
- Actualizar docs/diagramas afectados por cambios de flujo, arquitectura, modelo,
  testing o integraciones; fuentes versionables en `docs/diagrams/` y regenerar
  derivados según su README. Asignar fuente y renderizados al mismo owner.
- Hallazgos fuera del corte a BACKLOG, referenciados brevemente desde STATUS;
  no convertir STATUS en backlog/bitácora. Conservar sólo snapshot + última ejecución.
- Memoria selectiva: Lead decide `IGNORE / UPDATE_EXISTING / ADD`; preferir enlaces
  y actualizar entradas existentes. Nada reusable nuevo → no tocar memoria.
  No guardar secretos, logs completos, chats, razonamientos, diffs ni datos temporales.
  Compactar según [política de memoria](docs/ai-memory/README.md).
- Resumen final compacto: implementado/archivos, tests/resultados, decisiones,
  límites/pendientes, memoria, commits y próximo paso/intervención humana.
