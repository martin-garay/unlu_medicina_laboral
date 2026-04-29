# Daily Plan
## Fecha
2026-04-29

## Nombre
Backoffice read-only modules

## Objetivo del dia
Continuar la implementación incremental de módulos administrativos read-only ya especificados, sin tocar storage privado ni descarga/visualización de archivos médicos.

## Estado actual resumido

- `ConversacionResource` existe con listado read-only.
- `ConversacionResource` tiene pantalla de historial read-only con permiso `conversaciones.historial.view`.
- La especificación fina vive en `docs/backoffice/module-specs.md`.
- `I4 - Storage privado de certificados` sigue fuera de alcance.
- No existen todavía `AvisoResource`, `AnticipoCertificadoResource` ni `AuditoriaAdministrativaResource`.

## Reglas de ejecucion

- Ejecutar milestones en orden.
- No implementar `I4`.
- No descargar, previsualizar ni exponer archivos médicos.
- No exponer `Aviso.certificado_base64`.
- No exponer `ConversacionMensaje.payload_crudo` completo.
- No exponer metadata completa por defecto.
- No agregar acciones de cambio de estado.
- No implementar Dusk/Playwright en esta daily.
- Cada milestone con código debe correr `make test` y `git diff --check`.

---

## P0
### ID
P0

### Nombre
Abrir daily de continuación read-only

### Objetivo
Crear la daily que ordena los próximos módulos read-only del backoffice.

### Alcance
- crear `plan_dev/daily/2026-04-29-02-backoffice-readonly-modules.md`
- dejar esta daily como próximo frente recomendado en `plan_dev/STATUS.md`
- crear dailies separadas para usuarios/roles y dashboard/reportes

### Validacion automatica obligatoria
- `git diff --check`

### Estado
`done`

---

## P1
### ID
P1

### Nombre
Agregar filtros y búsqueda avanzada a ConversacionResource

### Objetivo
Completar la tabla de conversaciones con filtros operativos definidos en `docs/backoffice/module-specs.md`.

### Alcance
- mantener búsqueda por `uuid`, `wa_number` y `dni`
- agregar filtros:
  - `canal`
  - `tipo_flujo`
  - `estado_actual`
  - `activa`
  - rango `ultimo_mensaje_recibido_en`
  - rango `finalizada_en`
  - rango `created_at`
- agregar tests Livewire/Filament para búsqueda y filtros principales

### No incluye
- nuevos permisos
- cambios de estado
- exportaciones
- detalle adicional de historial

### Validacion automatica obligatoria
- `make test`
- `git diff --check`

### Estado
`done`

### Resultado de ejecucion
- Se agregaron filtros a `ConversacionResource` por canal, flujo, estado actual, activa y rangos de fechas.
- Se mantuvo búsqueda por `uuid`, `wa_number` y `dni`.
- Se agregaron tests Livewire/Filament de búsqueda y filtros.

### Validacion ejecutada
- `make test`: `154 passed`, `560 assertions`.
- `git diff --check`: sin errores.

### Commit sugerido
- `feat: agregar filtros de conversaciones`

---

## P2
### ID
P2

### Nombre
Implementar AvisoResource listado read-only

### Objetivo
Agregar consulta inicial de avisos de ausencia en Filament, sin detalle complejo ni acciones de estado.

### Alcance
- crear `AvisoResource`
- listado read-only
- permiso `avisos.view`
- columnas operativas definidas en `docs/backoffice/module-specs.md`
- búsqueda por `dni`, `nombre_completo`, `legajo`, `wa_number`
- filtros básicos por `estado`, `tipo`, `tipo_ausentismo`, `sede`
- tests de acceso, listado, búsqueda y no exposición de `certificado_base64`

### No incluye
- verificar/observar/cancelar/invalidar aviso
- asociar o desasociar certificados
- descargar o visualizar certificados
- mostrar `certificado_base64`

### Validacion automatica obligatoria
- `make test`
- `git diff --check`

### Estado
`done`

### Resultado de ejecucion
- Se creo `AvisoResource` con listado read-only.
- Se agregaron columnas operativas, búsqueda por `dni`, `nombre_completo`, `legajo`, `wa_number` y filtros por `estado`, `tipo`, `tipo_ausentismo`, `sede`.
- Se bloqueo create/edit/delete y acciones masivas.
- Se agregaron tests de acceso, listado, búsqueda, filtros y no exposición de `certificado_base64`.

### Validacion ejecutada
- `make test`: `163 passed`, `604 assertions`.
- `git diff --check`: sin errores.

### Commit sugerido
- `feat: agregar listado read-only de avisos`

---

## P3
### ID
P3

### Nombre
Agregar detalle read-only de avisos

### Objetivo
Agregar pantalla de detalle de aviso con relaciones principales sin exponer datos sensibles.

### Alcance
- página `View` read-only
- secciones de datos del agente, aviso, fechas, contacto y observaciones
- enlaces o referencias a conversación asociada
- referencias a anticipos N a N y legacy si aplica
- tests de detalle, relaciones y no exposición de `certificado_base64`

### No incluye
- acciones de cambio de estado
- edición
- descarga/preview de certificados

### Validacion automatica obligatoria
- `make test`
- `git diff --check`

### Estado
`done`

### Resultado de ejecucion
- Se agrego pagina `View` read-only para avisos.
- Se agrego accion visual de ojo en el listado para abrir el detalle.
- El detalle muestra datos del agente, datos del aviso, observaciones, conversacion asociada y anticipos N a N/legacy.
- Se evito exponer `certificado_base64` y metadata tecnica completa.
- Se agregaron tests de accion de detalle, acceso directo, relaciones y no exposicion de datos sensibles.

### Validacion ejecutada
- `make test`: `166 passed`, `632 assertions`.
- `git diff --check`: sin errores.

### Commit sugerido
- `feat: agregar detalle read-only de avisos`

---

## P4
### ID
P4

### Nombre
Implementar AnticipoCertificadoResource listado read-only

### Objetivo
Agregar consulta de anticipos/certificados sin acceso a archivos médicos.

### Alcance
- crear `AnticipoCertificadoResource`
- listado read-only
- permiso `certificados.view`
- columnas operativas
- búsqueda por `numero_anticipo`, `nombre_completo`, `legajo`, `wa_number`, `uuid`
- filtros por `estado`, `tipo_certificado`, `sede`, fechas
- tests de acceso, listado, búsqueda y ausencia de descarga/preview

### No incluye
- storage privado
- descarga de archivos
- preview de archivos
- validar/rechazar archivos

### Validacion automatica obligatoria
- `make test`
- `git diff --check`

### Estado
`done`

### Resultado de ejecucion
- Se creo `AnticipoCertificadoResource` con label operativo de certificados medicos.
- Se agrego listado read-only protegido por `certificados.view`.
- Se agregaron columnas operativas, contador de archivos, busqueda por `uuid`, `numero_anticipo`, `nombre_completo`, `legajo`, `wa_number` y filtros por `estado`, `tipo_certificado`, `sede`, `registrado_en` y `created_at`.
- Se bloquearon create/edit/delete, acciones de fila y acciones masivas.
- Se agregaron tests de acceso, listado, busqueda, filtros, bloqueo de escritura y ausencia de descarga/preview.

### Validacion ejecutada
- `make test`: `175 passed`, `686 assertions`.
- `git diff --check`: sin errores.

### Commit sugerido
- `feat: agregar listado read-only de certificados`

---

## P5
### ID
P5

### Nombre
Agregar detalle y metadata de archivos de anticipos

### Objetivo
Mostrar detalle read-only de anticipo y metadata segura de archivos asociados.

### Alcance
- página `View` read-only
- relaciones con conversación, aviso legacy, avisos N a N
- tabla/sección de archivos con metadata segura:
  - `nombre_original`
  - `mime_type`
  - `extension`
  - `size_bytes`
  - `estado_validacion`
  - `motivo_rechazo`
  - `hash_archivo`
  - `storage_disk`
  - `created_at`
- tests de no exposición de `storage_path` como link público

### No incluye
- mostrar `storage_path` completo por defecto
- descargar archivos
- previsualizar archivos
- exponer links públicos

### Validacion automatica obligatoria
- `make test`
- `git diff --check`

### Estado
`done`

### Resultado de ejecucion
- Se agrego pagina `View` read-only para certificados medicos/anticipos.
- Se agrego accion visual de ojo en el listado para abrir el detalle.
- El detalle muestra datos del agente, datos del certificado, observaciones, conversacion asociada, aviso legacy, avisos N a N y archivos asociados.
- Los archivos se muestran solo con metadata permitida y no exponen `storage_path`, descarga, preview ni URL publica.
- Se agregaron tests de accion de detalle, acceso directo, relaciones, metadata segura y no exposicion de rutas internas.

### Validacion ejecutada
- `make test`: `178 passed`, `723 assertions`.
- `git diff --check`: sin errores.

### Commit sugerido
- `feat: agregar detalle read-only de certificados`

---

## P6
### ID
P6

### Nombre
Implementar AuditoriaAdministrativaResource read-only

### Objetivo
Agregar consulta de auditoría administrativa con filtros principales.

### Alcance
- crear `AuditoriaAdministrativaResource`
- permiso `auditoria.view`
- listado read-only
- detalle read-only
- filtros por actor, action, origin, auditable_type y fecha
- tests de permisos por rol y bloqueo create/edit/delete

### No incluye
- edición de auditoría
- borrado de auditoría
- exportación

### Validacion automatica obligatoria
- `make test`
- `git diff --check`

### Estado
`pending`
