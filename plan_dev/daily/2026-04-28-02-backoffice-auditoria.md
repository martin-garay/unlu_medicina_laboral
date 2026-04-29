# Daily Plan
## Fecha
2026-04-28, segunda daily del dia

## Objetivo del dia
Preparar el siguiente frente del backoffice: `I3 - Auditoria administrativa base`.

Este daily no debe crear Resources de avisos/certificados ni implementar storage privado. El objetivo es dejar una base auditable para futuras acciones administrativas antes de habilitar operaciones de negocio desde Filament.

---

## Estado actual resumido

- `I1 - Base tecnica de Filament`: `done`.
- `I2 - Auth, usuarios, roles y permisos`: `base_done`.
- Panel Filament disponible en `/admin`.
- Acceso al panel restringido por permiso `backoffice.access`.
- Roles y permisos base disponibles por seeder idempotente.
- No existe todavia tabla/modelo de auditoria administrativa.
- No existe todavia `AuditoriaService`.
- No existen todavia Application Actions administrativas.
- `LOG-001` sigue pendiente y limita cualquier logging nuevo con datos sensibles.
- `BO-002` sigue pendiente y bloquea storage/descarga de certificados.

---

## Decisiones propuestas para este frente

- Crear auditoria administrativa como tabla propia, separada de logs tecnicos.
- No guardar contenido medico, archivos ni payloads completos en auditoria.
- Registrar metadata minima y estructurada.
- Usar `actor_user_id` nullable para permitir eventos de comandos/jobs futuros.
- Usar campos polimorficos `auditable_type` y `auditable_id` para entidad afectada.
- Usar `action` y `origin` como strings controlados inicialmente desde config o constantes.
- Crear un servicio de dominio/aplicacion reutilizable antes de integrarlo con Filament.
- Diferir auditoria de lectura de archivos medicos hasta `I4`, cuando exista storage privado.

---

## Reglas de ejecucion de esta segunda daily

- Ejecutar los milestones en orden.
- No avanzar a `I4` hasta cerrar `I3`.
- No registrar datos sensibles completos.
- Si aparece una duda de politica de PII/logs, referenciar `LOG-001` y frenar si afecta el alcance.
- Todo cambio runtime debe tener tests y `make test`.
- Todo milestone cerrado con cambios debe quedar en commit chico.

---

## A0
### ID
A0

### Nombre
Abrir daily de auditoria administrativa

### Objetivo
Crear esta segunda daily para ordenar `I3` despues del cierre de permisos.

### Alcance
- crear `plan_dev/daily/2026-04-28-02-backoffice-auditoria.md`
- actualizar `plan_dev/STATUS.md`
- dejar `A1` como primer milestone pendiente

### No incluye
- migrations
- modelos
- servicios
- Resources de Filament

### Validacion automatica obligatoria
- `git diff --check`

### Validacion manual sugerida
- confirmar que este daily no salta a Resources ni storage privado

### Estado
`done`

### Resultado de ejecucion
- Daily creado para planificar y ejecutar `I3`.
- `A1` queda como primer milestone pendiente.

---

## A1
### ID
A1

### Nombre
Diseñar contrato de auditoria administrativa

### Objetivo
Definir campos, tipos de evento, origenes y reglas de minimizacion antes de crear tablas.

### Alcance
- actualizar `docs/backoffice/security-and-audit.md`
- definir contrato minimo de evento administrativo
- definir acciones iniciales esperadas
- definir origenes iniciales: `filament`, `command`, `job`, `system`
- documentar que contenido medico y payloads completos quedan prohibidos
- actualizar `plan_dev/STATUS.md`

### No incluye
- crear migrations
- crear modelos
- crear servicios

### Dependencias
- `A0` done

### Validacion automatica obligatoria
- `git diff --check`

### Validacion manual sugerida
- revisar que el contrato sea suficiente para acciones futuras de certificados, avisos y asociaciones

### Stop conditions especificas
- si el contrato exige definir politica completa de PII, dejar `needs_review` y referenciar `LOG-001`

### Commit sugerido
- `docs: definir contrato de auditoria administrativa`

### Estado
`done`

### Resultado de ejecucion
- Se actualizo `docs/backoffice/security-and-audit.md` con el contrato minimo de auditoria administrativa.
- Se definieron campos base, origenes iniciales `filament`, `command`, `job` y `system`.
- Se documentaron acciones iniciales esperadas para roles, permisos, avisos, certificados, asociaciones, reportes y usuarios.
- Se dejo explicito que contenido medico, archivos, payloads completos y cuerpos de mensajes no deben guardarse en auditoria.
- La auditoria de lectura/descarga de archivos medicos queda diferida a `I4`, junto con storage privado.

### Validacion ejecutada
- `git diff --check`

---

## A2
### ID
A2

### Nombre
Crear tabla y modelo de auditoria administrativa

### Objetivo
Agregar persistencia minima para eventos de auditoria administrativa.

### Alcance
- crear migration de `auditoria_administrativa` o nombre equivalente documentado
- crear modelo Eloquent
- definir casts para metadata, before/after y timestamps
- agregar factory o helpers de test si corresponde
- actualizar `plan_dev/STATUS.md`

### No incluye
- integracion con Filament Actions
- auditoria de descarga de archivos medicos

### Dependencias
- `A1` done

### Validacion automatica obligatoria
- `make migrate`
- `make test`
- `git diff --check`

### Stop conditions especificas
- si se detecta colision con un modelo/evento existente, dejar `blocked`

### Commit sugerido
- `feat: agregar modelo de auditoria administrativa`

### Estado
`pending`

---

## A3
### ID
A3

### Nombre
Crear servicio de auditoria administrativa

### Objetivo
Crear una API interna para registrar eventos auditables sin acoplar la logica a Filament.

### Alcance
- crear servicio en `app/Domain/Auditoria/Services` o ubicacion equivalente
- registrar actor, accion, origen, entidad afectada, estado anterior/nuevo y metadata minima
- agregar tests unitarios/feature del servicio
- actualizar documentacion si cambia el contrato

### No incluye
- acciones de certificados/avisos
- lectura de archivos medicos

### Dependencias
- `A2` done

### Validacion automatica obligatoria
- `make test`
- `git diff --check`

### Commit sugerido
- `feat: agregar servicio de auditoria administrativa`

### Estado
`pending`

---

## A4
### ID
A4

### Nombre
Integrar auditoria en eventos administrativos base

### Objetivo
Usar el servicio de auditoria en operaciones administrativas ya existentes del backoffice cuando tenga sentido.

### Alcance
- auditar ejecucion del seeder de roles/permisos solo si se define como evento administrativo relevante
- preparar helpers para futuras Application Actions
- documentar integracion esperada con Filament
- actualizar tests

### No incluye
- auditar lectura/descarga de certificados medicos
- Resources de negocio

### Dependencias
- `A3` done

### Validacion automatica obligatoria
- `make test`
- `git diff --check`

### Stop conditions especificas
- si no hay evento administrativo real para integrar sin forzar diseño, marcar `needs_review` y cerrar con el servicio listo

### Commit sugerido
- `feat: integrar auditoria administrativa base`

### Estado
`pending`

---

## A5
### ID
A5

### Nombre
Cierre operativo de I3

### Objetivo
Consolidar estado, validar suite completa y dejar proximo frente recomendado.

### Alcance
- actualizar `docs/backoffice/implementation-plan.md`
- actualizar `plan_dev/STATUS.md`
- registrar pendientes nuevos en `plan_dev/BACKLOG.md` si aparecen
- dejar proximo paso recomendado

### Dependencias
- `A3` done como minimo, o `A4` done/needs_review si se intenta integracion

### Validacion automatica obligatoria
- `make test`
- `git diff --check`

### Commit sugerido
- `docs: cerrar auditoria administrativa base`

### Estado
`pending`

---

## Criterio de cierre

El frente queda listo si:

- existe contrato documental de auditoria
- existe tabla/modelo si se ejecuta A2
- existe servicio reutilizable si se ejecuta A3
- la suite queda verde
- `STATUS.md` deja claro si `I3` queda `done`, `base_done`, `needs_review` o `blocked`
