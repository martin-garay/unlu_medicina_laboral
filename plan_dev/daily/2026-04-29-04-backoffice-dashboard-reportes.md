# Daily Plan
## Fecha
2026-04-29

## Nombre
Backoffice dashboard, reports and exports

## Objetivo del dia
Implementar dashboard mínimo y planificar informes/exportaciones sin consultas pesadas ni jobs prematuros.

## Estado actual resumido

- No existe dashboard operativo propio.
- No existen reportes/exportaciones.
- La planificación fina está en `docs/backoffice/module-specs.md`.
- Deben existir Resources base antes de reportes más útiles.

## Reglas de ejecucion

- No implementar queries pesadas en request.
- No implementar exportaciones masivas sin jobs.
- No descargar ni exponer archivos médicos.
- Medir solo métricas simples en primera versión.
- Cada milestone con código debe correr `make test` y `git diff --check`.

---

## P0
### ID
P0

### Nombre
Abrir daily de dashboard y reportes

### Objetivo
Ordenar el frente de métricas e informes.

### Estado
`pending`

---

## P1
### ID
P1

### Nombre
Implementar dashboard mínimo

### Objetivo
Agregar widgets livianos para lectura operativa.

### Alcance
- avisos totales
- avisos por estado
- conversaciones activas
- conversaciones finalizadas
- anticipos por estado
- mensajes recibidos hoy
- mensajes inválidos hoy
- tests de métricas simples

### Estado
`pending`

---

## P2
### ID
P2

### Nombre
Planificar informes operativos

### Objetivo
Definir informes antes de implementar consultas o exportaciones.

### Alcance
- avisos por período
- anticipos por estado
- conversaciones por canal/estado
- mensajes inválidos
- actividad administrativa
- permisos y auditoría requeridos

### Estado
`pending`

---

## P3
### ID
P3

### Nombre
Definir exportaciones seguras

### Objetivo
Planificar exportaciones sin afectar performance ni seguridad.

### Alcance
- criterios para jobs en cola
- storage privado
- auditoría de exportación
- permisos específicos
- límites de rango de fechas

### No incluye
- implementar exportación real

### Estado
`pending`

