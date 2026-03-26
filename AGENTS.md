
---

## `AGENTS.md`

```md
# AGENTS.md

## Proyecto

Sistema MVP de Medicina Laboral UNLu implementado con Laravel + PostgreSQL + Docker, utilizando WhatsApp Cloud API como canal conversacional.

## Objetivo actual

Implementar una base sólida y mantenible para el motor de conversación y los flujos de negocio:

- aviso de ausencia
- anticipo de certificado médico

## Antes de tocar código

Leer obligatoriamente:

- `README.md`
- `docs/README.md`
- `docs/05-motor-de-conversacion.md`

Cuando existan, también leer:

- `docs/06-flujo-aviso-ausencia.md`
- `docs/07-flujo-anticipo-certificado.md`
- `docs/08-validaciones-y-reglas.md`
- `docs/09-scheduler-e-inactividad.md`
- `docs/diagrams/README.md`

## Reglas de diseño

- No hardcodear textos en el código.
  - Usar `lang/es/*.php`
- No hardcodear parámetros configurables.
  - Usar `config/*.php`
- Controllers livianos.
- La lógica de negocio debe vivir en servicios o handlers.
- Toda interacción de usuario debe quedar asociada a una conversación.
- No borrar conversaciones ni mensajes cancelados, expirados o fallidos.
- Una conversación es una unidad técnica de trazabilidad.
- Un aviso es una entidad de negocio separada.
- Un anticipo de certificado es una entidad de negocio separada y requiere aviso previo.
- Los automatismos de inactividad deben resolverse con Laravel Scheduler.
- Los mensajes largos deben vivir en templates Blade.
- Los diagramas versionables viven en `docs/diagrams/`.
- Si cambian flujos o estructura relevante, actualizar los diagramas afectados.

## Criterios de modelado

### Conversación
Representa una sesión viva entre el usuario y el bot.

Debe soportar:

- estado actual
- tipo de flujo
- intentos
- timestamps
- timeout
- cierre por cancelación
- cierre por inactividad
- historial de mensajes y eventos

### Mensajes
Cada mensaje entrante y saliente debe quedar persistido y asociado a una conversación.

Registrar cuando sea posible:

- dirección (`in` / `out`)
- tipo de mensaje
- contenido
- payload crudo
- validez
- motivo de invalidez
- paso del flujo

### Eventos
Registrar eventos técnicos y de trazabilidad, por ejemplo:

- cambio de estado
- validación fallida
- recordatorio por inactividad
- cancelación automática
- cancelación manual
- creación de aviso
- creación de anticipo

## Convenciones de implementación

- Preferir componentes pequeños y extensibles.
- Evitar `switch` gigantes para manejar flujos completos.
- Diseñar los pasos del flujo para que cada uno tenga:
  - dato esperado
  - validación
  - respuesta de éxito
  - respuesta de error
  - cantidad de intentos
  - transición al siguiente paso
- Separar:
  - normalización de mensajes
  - validación
  - persistencia
  - respuesta
  - materialización de entidades de negocio

## Integraciones futuras

La identificación real del trabajador y otras integraciones externas deben quedar desacopladas detrás de interfaces o servicios mockeables.

## Qué no asumir

- No asumir que todo mensaje recibido es texto.
- No asumir que una conversación debe derivar siempre en un aviso.
- No asumir que una conversación cancelada debe reutilizarse.
- No asumir que los textos o catálogos serán fijos en el tiempo.

## Meta técnica de corto plazo

Dejar el proyecto preparado para:

- trazabilidad completa
- mantenimiento simple
- incorporación de nuevas validaciones
- incorporación de nuevos pasos o subflujos
- administración futura de mensajes y parámetros
- documentación visual viva mantenible desde Git y por agentes


## Rutina de trabajo diaria

### Archivos de referencia obligatorios
Antes de ejecutar cualquier tarea relevante, leer en este orden:

1. `AGENTS.md`
2. `plan_dev/MASTER_PLAN.md`
3. `plan_dev/STATUS.md`
4. el archivo diario correspondiente en `plan_dev/daily/YYYY-MM-DD.md`

Si alguno no existe, dejarlo explícito en el resumen final.

### Rol de cada archivo operativo

- `AGENTS.md`: reglas estables de trabajo, diseño, validación y actualización documental.
- `plan_dev/MASTER_PLAN.md`: roadmap general y criterio de orden a mediano plazo.
- `plan_dev/STATUS.md`: estado consolidado actual y resultado de la última ejecución relevante.
- `plan_dev/daily/YYYY-MM-DD.md`: plan operativo del día y orden de milestones.
- `plan_dev/BACKLOG.md`: hallazgos y pendientes fuera del alcance del día.

Si dos archivos parecen contradecirse, usar esta precedencia:
1. `AGENTS.md`
2. `plan_dev/daily/YYYY-MM-DD.md`
3. `plan_dev/STATUS.md`
4. `plan_dev/MASTER_PLAN.md`

Los archivos fechados legacy en `plan_dev/` solo deben usarse como contexto histórico o insumo puntual, no como fuente de verdad activa, salvo que `STATUS.md` o el plan diario los referencien explícitamente.

---

## Fuente de verdad del trabajo

- `plan_dev/MASTER_PLAN.md` define el roadmap general.
- `plan_dev/STATUS.md` define el estado actual consolidado.
- `plan_dev/daily/YYYY-MM-DD.md` define qué se ejecuta hoy.
- `plan_dev/BACKLOG.md` concentra pendientes no priorizados o hallazgos fuera del alcance del día.

No usar conversaciones anteriores como única fuente de verdad si ya existe información más actual en esos archivos.
No tratar archivos históricos fechados como plan activo en competencia con esta estructura.

---

## Regla de ejecución

Trabajar siempre sobre el **primer milestone pendiente** del plan diario.

No saltar milestones bloqueados salvo que el propio plan diario lo autorice explícitamente.

Para cada milestone seguir esta secuencia:

1. analizar el estado actual del repo
2. proponer plan corto de archivos a crear/modificar
3. implementar cambios
4. correr validaciones obligatorias
5. actualizar `plan_dev/STATUS.md`
6. decidir si el milestone queda:
   - `done`
   - `blocked`
   - `needs_review`

---

## Reglas generales de stop/fix

No avanzar al siguiente milestone si falla una validación obligatoria.

Frenar y dejar el milestone en `blocked` si:
- hay una ambigüedad funcional relevante;
- hay más de una decisión técnica razonable y no existe definición en docs;
- falta acceso a sistema externo, credencial, contrato o dependencia necesaria;
- el cambio deja de ser acotado y exige una refactorización mayor;
- no se puede validar el resultado de forma mínima y segura.

Marcar `needs_review` si:
- el cambio quedó técnicamente listo;
- pero requiere validación o decisión humana antes de seguir.

Se permite un intento razonable de corrección frente a errores menores.  
No encadenar correcciones indefinidas sin actualizar el estado y sin dejar claro el bloqueo.

---

## Validación obligatoria

Todo milestone debe definir:
- validación automática obligatoria
- validación manual sugerida
- condiciones específicas de stop

Si el cambio toca lógica relevante:
- agregar o ajustar tests en el mismo commit cuando corresponda
- correr los tests definidos por el milestone

Si el entorno no permite correr tests o validaciones:
- dejarlo explícito en `plan_dev/STATUS.md`
- no afirmar que el cambio quedó validado completamente

---

## Actualización de estado

Al terminar cada milestone, actualizar `plan_dev/STATUS.md` con:
- fecha y hora
- milestone trabajado
- resultado (`done`, `blocked`, `needs_review`)
- resumen corto de cambios
- validaciones ejecutadas
- bloqueos o decisiones pendientes
- próximo paso sugerido

No dejar el repo en un estado donde no quede claro qué pasó en la última ejecución.
`STATUS.md` debe funcionar como snapshot consolidado + última ejecución, no como backlog paralelo.

---

## Documentación viva

Si un cambio impacta de forma relevante en:
- flujos
- arquitectura
- modelo de datos
- testing
- integraciones
- diagramas

actualizar la documentación correspondiente en `docs/` y/o `docs/diagrams/`.

No postergar sistemáticamente la actualización documental para “más adelante”.

---

## Diagramas como código

Revisar `docs/diagrams/` cuando el cambio afecte:
- caminos conversacionales
- estructura de clases
- modelo de datos

Los diagramas en formato texto forman parte de la documentación viva del repo y deben mantenerse alineados con los cambios estructurales importantes.

---

## Hallazgos fuera del alcance

Si durante la ejecución aparecen:
- mejoras no prioritarias
- deuda técnica no crítica
- refactors futuros
- tareas que no entran en el milestone actual

registrarlas en `plan_dev/BACKLOG.md` o en la sección correspondiente de `plan_dev/STATUS.md`, sin desviar la ejecución del objetivo del día.

Si el hallazgo ya fue volcado en `plan_dev/BACKLOG.md`, en `STATUS.md` alcanza con dejar una referencia breve; evitar duplicar backlog completo en ambos lugares.

---

## Resumen final esperado por milestone

Cada ejecución relevante debe dejar un resumen con:
- archivos creados o modificados
- qué se implementó o ajustó
- qué validaciones se ejecutaron
- qué quedó pendiente
- si hace falta intervención humana o no
