# Daily Plan
## Fecha
YYYY-MM-DD

## Objetivo del día
Describir en una o dos líneas qué se quiere lograr hoy.

## Relación con otros archivos

- `AGENTS.md` define reglas estables de ejecución.
- `plan_dev/MASTER_PLAN.md` define el orden macro del roadmap.
- `plan_dev/STATUS.md` define el estado consolidado y la última ejecución.
- Este archivo define solo el trabajo operativo del día.

Si este plan diario contradice un documento histórico fechado, prevalece este archivo.

---

## Reglas de ejecución del día

- Leer antes de empezar:
  - `AGENTS.md`
  - `plan_dev/MASTER_PLAN.md`
  - `plan_dev/STATUS.md`
  - este archivo
- Ejecutar los milestones en orden.
- No saltar un milestone bloqueado, salvo que este archivo lo autorice explícitamente.
- Si falla una validación obligatoria, no avanzar al siguiente milestone.
- Actualizar `plan_dev/STATUS.md` al cerrar cada milestone o al quedar bloqueado.
- Si un cambio impacta flujo, arquitectura, modelo, testing o diagramas, actualizar documentación correspondiente.

---

## Milestone 1
### Nombre
Ejemplo: cerrar confirmación final del anticipo

### ID
M1

### Objetivo
Qué resultado concreto se espera.

### Alcance
Qué entra y qué no entra.

### Dependencias
- docs, decisiones o milestones previos necesarios:

### Validación automática obligatoria
- `make test`
- o tests específicos:
  - `php artisan test --filter ...`

### Validación manual sugerida
- 
- 

### Stop conditions específicas
- si falta definición funcional sobre ...
- si el modelo actual no soporta ...
- si aparece una refactorización transversal no prevista

### Entregable esperado
- 

### Si queda bloqueado, ¿se puede avanzar al siguiente milestone?
- `no`
- solo cambiar a `sí` si hay autorización explícita y escrita en este plan

### Estado
`pending`

---

## Milestone 2
### Nombre
...

### ID
M2

### Objetivo
...

### Alcance
...

### Dependencias
- 

### Validación automática obligatoria
- 

### Validación manual sugerida
- 

### Stop conditions específicas
- 

### Entregable esperado
- 

### Si queda bloqueado, ¿se puede avanzar al siguiente milestone?
- `no`

### Estado
`pending`

---

## Milestone 3
### Nombre
...

### ID
M3

### Objetivo
...

### Alcance
...

### Dependencias
- 

### Validación automática obligatoria
- 

### Validación manual sugerida
- 

### Stop conditions específicas
- 

### Entregable esperado
- 

### Si queda bloqueado, ¿se puede avanzar al siguiente milestone?
- `no`

### Estado
`pending`

---

## Criterio de cierre del día
- actualizar `plan_dev/STATUS.md` con el último milestone ejecutado y su resultado
- marcar el milestone correspondiente como `done`, `blocked` o `needs_review`
- dejar explícito si hace falta intervención humana antes de la próxima ejecución

## Decisiones humanas esperadas al cierre del día
- 
- 

---

## Notas del día
- 
- 
