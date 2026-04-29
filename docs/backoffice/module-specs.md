# Especificación de módulos administrativos

## Objetivo

Este documento baja a detalle las opciones administrativas del backoffice antes de implementar Resources de Filament.

Debe permitir abrir dailies de implementación concretas, por ejemplo:

- implementar `ConversacionResource` read-only con tests
- implementar `AvisoResource` read-only con tests
- implementar `AnticipoCertificadoResource` read-only sin descarga de archivos

No reemplaza `implementation-plan.md`: lo complementa con especificación de pantallas, permisos, relaciones, restricciones y tests esperados por módulo.

## Estado actual

Base disponible:

- Filament instalado y panel en `/admin`.
- Autenticación administrativa con `App\Models\User`.
- Acceso al panel protegido por `backoffice.access`.
- Roles iniciales con Spatie Laravel Permission: `admin`, `auditor`, `director`.
- Permisos base: `dashboard.view`, `avisos.view`, `certificados.view`, `conversaciones.view`, `auditoria.view`, `reportes.view`, entre otros.
- Auditoría administrativa base cerrada:
  - tabla `auditoria_administrativa`
  - modelo `App\Models\AuditoriaAdministrativa`
  - servicio `App\Domain\Auditoria\Services\AuditoriaAdministrativaService`
  - integración inicial en seeder de roles/permisos

No disponible todavía:

- Resources funcionales en `app/Filament/Resources`.
- UI de usuarios, roles o permisos.
- storage privado real de certificados.
- descarga o visualización segura de archivos médicos.
- acciones administrativas de cambio de estado.
- configuración de Dusk o Playwright.

## Arquitectura obligatoria

La arquitectura de módulos administrativos debe respetar esta cadena:

```text
Filament
    ↓
Application Actions
    ↓
Domain Services / Domain Rules
    ↓
Models / Persistence / Events / Audit
```

Responsabilidades:

- Filament: navegación, tablas, filtros, búsqueda, formularios simples, páginas de detalle, widgets, RelationManagers, botones visuales y mensajes al operador.
- Application Action: operación de negocio reutilizable desde Filament, API futura, Jobs, comandos o tests.
- Domain Service / Domain Rule: reglas reutilizables, validaciones de dominio, asociación entre entidades, storage, auditoría o reportes.
- Models: relaciones Eloquent, casts, scopes simples y persistencia.

Reglas:

- No usar el término `UseCase` para clases nuevas.
- Diferenciar siempre Filament Action de Application Action.
- No ubicar reglas de negocio complejas en closures de Filament.
- No implementar State Machine en estos módulos.
- No implementar multi-tenancy ni separación por sedes.
- No implementar permisos por campo o columna.

## Estrategia de testing

El proyecto usa PHPUnit mediante Laravel test runner. No usa Pest.

Comandos base:

```bash
make test
```

Para cortes puntuales:

```bash
make artisan CMD='test --filter=ConversacionResourceTest'
```

La prioridad para módulos read-only es:

- tests PHPUnit/Livewire/Filament para Resources, Pages y RelationManagers
- tests HTTP de permisos cuando aporten claridad
- tests de no exposición de datos sensibles
- tests de filtros, búsquedas y columnas críticas

No incorporar Dusk ni Playwright todavía. Documentarlos como opción futura para flujos críticos cuando exista una necesidad concreta:

- login administrativo completo en navegador real
- navegación principal
- permisos básicos del panel
- visualización de detalle
- verificación de que no se expongan links públicos a archivos sensibles

### Tests mínimos por Resource read-only

Cada Resource read-only debe cubrir:

- usuario sin `backoffice.access` no accede al panel
- usuario con `backoffice.access` y permiso del módulo accede
- rol autorizado puede listar
- rol autorizado puede ver detalle
- rol no autorizado no puede listar ni ver detalle
- búsqueda por campos principales funciona
- filtros principales funcionan
- relaciones principales se renderizan o quedan enlazadas sin error
- no aparecen acciones de crear, editar, borrar o cambio de estado
- no se exponen campos sensibles completos

### Seguridad de datos sensibles

Los tests deben asegurar:

- `Aviso.certificado_base64` no aparece en listado ni detalle
- `AnticipoCertificadoArchivo.storage_path` no aparece como link público
- no hay acciones de descarga o preview de archivos médicos antes de I4
- `ConversacionMensaje.payload_crudo` no se muestra completo por defecto
- `ConversacionMensaje.metadata` no se muestra completa por defecto
- `Conversacion.metadata` no se muestra completa por defecto si contiene datos sensibles
- campos de texto potencialmente sensibles se muestran solo donde el módulo lo justifica

## Relevamiento de modelos

### `Aviso`

Uso administrativo esperado: consulta de avisos de ausencia y navegación hacia conversación y anticipos/certificados asociados.

Campos operativos:

- `id`
- `dni`
- `nombre_completo`
- `legajo`
- `sede`
- `jornada_laboral`
- `tipo`
- `estado`
- `tipo_ausentismo`
- `fecha_inicio`
- `fecha_fin`
- `cantidad_dias`
- `motivo`
- `domicilio_circunstancial`
- `observaciones`
- `wa_number`
- `created_at`

Campos sensibles o restringidos:

- `certificado_base64`: no mostrar
- `metadata`: no mostrar completa por defecto

Relaciones:

- `conversacion()`
- `anticiposCertificado()` como relación N a N vigente
- `anticiposCertificadoLegacy()` solo para compatibilidad o diagnóstico

### `AnticipoCertificado`

Uso administrativo esperado: consulta de anticipos/certificados médicos sin acceso a binarios.

Campos operativos:

- `id`
- `uuid`
- `numero_anticipo`
- `wa_number`
- `nombre_completo`
- `legajo`
- `sede`
- `jornada_laboral`
- `tipo_certificado`
- `estado`
- `observaciones`
- `registrado_en`
- `created_at`

Campos restringidos:

- `metadata`: no mostrar completa por defecto

Relaciones:

- `conversacion()`
- `aviso()` legacy
- `avisos()` N a N vigente
- `archivos()` como metadata de adjuntos

### `AnticipoCertificadoArchivo`

Uso administrativo esperado: metadata read-only de archivos asociados.

Mostrar:

- `nombre_original`
- `mime_type`
- `extension`
- `size_bytes`
- `estado_validacion`
- `motivo_rechazo`
- `hash_archivo`
- `storage_disk`
- `created_at`

No mostrar:

- link público
- preview
- descarga
- `storage_path` completo por defecto
- `provider_file_id` salvo vista técnica futura
- `metadata` completa por defecto

### `Conversacion`

Uso administrativo esperado: trazabilidad conversacional read-only.

Campos operativos:

- `id`
- `uuid`
- `wa_number`
- `canal`
- `tipo_flujo`
- `estado_actual`
- `paso_actual`
- `activa`
- contadores de mensajes e intentos
- fechas de última actividad
- `finalizada_en`
- `motivo_finalizacion`
- `created_at`

Campos restringidos:

- `metadata`: no mostrar completa por defecto

Relaciones:

- `mensajes()`
- `eventos()`
- `aviso()`
- `anticipoCertificado()`

### `ConversacionMensaje`

Uso administrativo esperado: timeline read-only dentro de conversación.

Mostrar:

- `direccion`
- `tipo_mensaje`
- `step_key`
- `contenido_texto` con criterio de longitud
- `es_valido`
- `motivo_invalidez`
- `message_key`
- `template_name`
- `created_at`

No mostrar por defecto:

- `payload_crudo` completo
- `metadata` completa

### `ConversacionEvento`

Uso administrativo esperado: timeline técnico dentro de conversación.

Mostrar:

- `tipo_evento`
- `step_key`
- `descripcion`
- `codigo`
- `created_at`

No mostrar por defecto:

- `metadata` completa

### `AuditoriaAdministrativa`

Uso administrativo esperado: consulta read-only de auditoría administrativa.

Mostrar:

- `id`
- `actor`
- `action`
- `origin`
- `auditable_type`
- `auditable_id`
- `created_at`

Detalle:

- `before_values`
- `after_values`
- `metadata`

Restricción:

- solo lectura
- sin crear, editar o borrar desde backoffice

### `User`

Uso administrativo actual:

- autenticación administrativa
- roles y permisos Spatie
- acceso al panel por `backoffice.access`

La UI de gestión de usuarios/roles no forma parte del primer bloque read-only.

## Orden recomendado

Orden de menor riesgo:

1. `ConversacionResource` read-only.
2. `AvisoResource` read-only.
3. `AnticipoCertificadoResource` read-only, sin descarga ni preview.
4. `AuditoriaAdministrativaResource` read-only.
5. Dashboard mínimo.
6. Acciones administrativas sobre avisos/certificados.
7. `I4 - Storage privado de certificados`.
8. Visualización/descarga segura de archivos.
9. Informes/exportaciones.
10. Configuración administrativa.

Justificación:

- Conversaciones permite validar navegación, permisos, listados, detalle y trazabilidad sin tocar archivos médicos.
- Avisos agrega entidad de negocio principal con un campo sensible claro (`certificado_base64`) que sirve para endurecer tests de no exposición.
- Anticipos/certificados queda después porque roza archivos médicos, aunque inicialmente solo muestre metadata.
- Auditoría read-only requiere definir si `director` queda excluido como ya indica la matriz.
- Dashboard e informes conviene hacerlos cuando existan Resources principales y consultas estabilizadas.

## Módulo A: Conversaciones read-only

### Objetivo

Implementar `ConversacionResource` como primer módulo de trazabilidad conversacional.

### Navegación

- Grupo sugerido: `Trazabilidad`
- Label: `Conversaciones`
- Permiso requerido: `conversaciones.view`

### Listado

Columnas:

- `id`
- `wa_number`
- `dni`
- `canal`
- `tipo_flujo`
- `estado_actual`
- `paso_actual`
- `activa`
- `cantidad_mensajes_recibidos`
- `cantidad_mensajes_enviados`
- `cantidad_mensajes_validos`
- `cantidad_mensajes_invalidos`
- `cantidad_intentos_totales`
- `ultimo_mensaje_recibido_en`
- `ultimo_mensaje_enviado_en`
- `finalizada_en`
- `created_at`

Búsqueda:

- `uuid`
- `wa_number`
- `dni`

Filtros:

- `canal`
- `tipo_flujo`
- `estado_actual`
- `activa`
- `tipo`
- `estado`
- rango `ultimo_mensaje_recibido_en`
- rango `finalizada_en`
- rango `created_at`

Orden default:

- `created_at desc`

### Detalle

Secciones:

- resumen de conversación
- estado y paso actual
- contadores
- fechas relevantes
- relaciones de negocio
- timeline de mensajes
- timeline de eventos

No mostrar:

- `payload_crudo` completo
- `metadata` completa
- acciones de modificación o reproceso

### Relaciones

Primera versión:

- mostrar mensajes y eventos como RelationManagers read-only o secciones/tablas read-only en detalle
- enlazar aviso asociado si existe
- enlazar anticipo asociado si existe

### Acciones

Permitido:

- `View`

No permitido:

- crear
- editar
- borrar
- responder mensajes
- reenviar mensajes
- reprocesar conversación
- modificar estado

### Tests esperados

- `admin`, `auditor` y `director` con `conversaciones.view` pueden acceder.
- usuario sin `backoffice.access` no accede al panel.
- usuario con `backoffice.access` pero sin `conversaciones.view` no accede al Resource.
- listado carga registros.
- búsqueda por `wa_number`, `dni` y `uuid` funciona.
- filtros `canal`, `activa`, `tipo_flujo` y fecha funcionan.
- detalle carga.
- mensajes se muestran ordenados por `created_at`.
- eventos se muestran ordenados por `created_at`.
- `payload_crudo` no aparece completo por defecto.
- `metadata` completa no aparece por defecto.
- acciones de crear/editar/borrar no aparecen o quedan denegadas.

### Milestones sugeridos

1. Crear `ConversacionResource` con listado y permisos.
2. Agregar filtros y búsqueda.
3. Agregar página de detalle.
4. Agregar RelationManagers o secciones read-only de mensajes/eventos.
5. Agregar tests de seguridad de datos sensibles.

Commits sugeridos:

- `feat: agregar resource read-only de conversaciones`
- `feat: agregar filtros de conversaciones`
- `feat: agregar detalle de conversaciones`
- `feat: agregar timeline read-only de conversaciones`
- `test: cubrir resource de conversaciones`

## Módulo B: Avisos read-only

### Objetivo

Implementar `AvisoResource` como consulta de avisos de ausencia, sin acciones de estado.

### Navegación

- Grupo sugerido: `Medicina laboral`
- Label: `Avisos de ausencia`
- Permiso requerido: `avisos.view`

### Listado

Columnas:

- `id`
- `dni`
- `nombre_completo`
- `legajo`
- `sede`
- `tipo`
- `estado`
- `tipo_ausentismo`
- `fecha_inicio`
- `fecha_fin`
- `cantidad_dias`
- `wa_number`
- `created_at`

No mostrar:

- `certificado_base64`
- `metadata` completa
- `observaciones` largas
- `domicilio_circunstancial` completo

Búsqueda:

- `dni`
- `nombre_completo`
- `legajo`
- `wa_number`

Filtros:

- `estado`
- `tipo`
- `tipo_ausentismo`
- `sede`
- rango `fecha_inicio`
- rango `fecha_fin`
- rango `created_at`

### Detalle

Secciones:

- datos del agente
- datos del aviso
- fechas
- contacto
- observaciones
- relaciones

Mostrar:

- `dni`
- `nombre_completo`
- `legajo`
- `sede`
- `jornada_laboral`
- `tipo`
- `estado`
- `tipo_ausentismo`
- `fecha_inicio`
- `fecha_fin`
- `cantidad_dias`
- `motivo`
- `domicilio_circunstancial`
- `observaciones`
- `wa_number`

No mostrar:

- `certificado_base64`
- `metadata` completa por defecto

### Relaciones

- conversación asociada
- anticipos/certificados N a N por `anticiposCertificado()`
- relación legacy `anticiposCertificadoLegacy()` solo como lectura técnica si se decide mostrarla

### Acciones

Permitido:

- `View`

No permitido:

- verificar aviso
- observar aviso
- cancelar aviso
- invalidar aviso
- asociar/desasociar certificados

### Tests esperados

- roles con `avisos.view` acceden.
- usuario sin permiso no accede.
- listado carga.
- búsqueda funciona.
- filtros básicos funcionan.
- detalle muestra datos y relaciones.
- `certificado_base64` no se expone.
- no aparecen acciones de edición ni cambio de estado.

## Módulo C: Anticipos/certificados read-only

### Objetivo

Implementar `AnticipoCertificadoResource` como consulta de históricos de certificados, sin descarga ni visualización de archivos.

### Navegación

- Grupo sugerido: `Medicina laboral`
- Label visible: `Certificados médicos`
- Modelo: `App\Models\AnticipoCertificado`
- Permiso requerido: `certificados.view`

### Listado

Columnas:

- `id`
- `numero_anticipo`
- `wa_number`
- `nombre_completo`
- `legajo`
- `sede`
- `tipo_certificado`
- `estado`
- `registrado_en`
- `created_at`
- `archivos_count`

Búsqueda:

- `uuid`
- `numero_anticipo`
- `nombre_completo`
- `legajo`
- `wa_number`

Filtros:

- `estado`
- `tipo_certificado`
- `sede`
- rango `registrado_en`
- rango `created_at`

### Detalle

Secciones:

- datos del agente
- datos del anticipo
- relaciones
- archivos asociados como metadata
- observaciones

No mostrar:

- links de descarga
- preview
- `storage_path` completo
- `metadata` completa por defecto

### Archivos asociados

Mostrar solo:

- `nombre_original`
- `mime_type`
- `extension`
- `size_bytes`
- `estado_validacion`
- `motivo_rechazo`
- `hash_archivo`
- `storage_disk`
- `created_at`

No implementar antes de I4:

- descargar archivo
- visualizar archivo
- validar archivo
- rechazar archivo
- generar temporary URL

### Tests esperados

- roles con `certificados.view` acceden.
- usuario sin permiso no accede.
- listado carga.
- búsqueda y filtros funcionan.
- detalle muestra relaciones.
- archivos se muestran solo como metadata permitida.
- no hay links públicos.
- `storage_path` no se expone como URL ni texto completo.
- no aparecen acciones de descarga/preview.

## Módulo D: Auditoría administrativa read-only

### Objetivo

Implementar `AuditoriaAdministrativaResource` para consulta de eventos administrativos.

### Navegación

- Grupo sugerido: `Seguridad`
- Label: `Auditoría administrativa`
- Permiso requerido: `auditoria.view`

### Listado

Columnas:

- `id`
- `actor.name`
- `action`
- `origin`
- `auditable_type`
- `auditable_id`
- `created_at`

Búsqueda:

- `action`
- `origin`
- `auditable_type`
- `auditable_id`
- actor por `name` o `email` si Filament lo permite sin query compleja

Filtros:

- actor
- `action`
- `origin`
- `auditable_type`
- rango `created_at`

### Detalle

Mostrar:

- actor
- action
- origin
- auditable_type
- auditable_id
- before_values
- after_values
- metadata
- created_at

### Acciones

Permitido:

- `View`

No permitido:

- crear
- editar
- borrar

### Tests esperados

- `admin` y `auditor` acceden.
- `director` no accede.
- usuario sin permiso no accede.
- listado carga.
- filtros funcionan.
- detalle muestra before/after/metadata.
- crear/editar/borrar no está disponible.

## Módulo E: Dashboard mínimo

### Objetivo

Crear widgets operativos livianos, sin reportes pesados.

Widgets candidatos:

- avisos totales
- avisos por estado
- conversaciones activas
- conversaciones finalizadas
- anticipos por estado
- mensajes recibidos hoy
- mensajes inválidos hoy

Reglas:

- no exponer PII innecesaria
- no hacer queries pesadas
- mover consultas a servicios si crecen
- restringir por `dashboard.view`

Tests esperados:

- usuario con `dashboard.view` ve widgets.
- usuario sin permiso no accede.
- métricas simples calculan valores correctos.
- no se muestran datos sensibles.

## Módulo F: Informes/exportaciones

### Objetivo

Solo planificación por ahora.

Reportes candidatos:

- avisos por período
- anticipos/certificados por estado
- actividad por operador
- conversaciones por estado/canal
- mensajes inválidos

Restricciones:

- exportaciones sensibles requieren storage privado
- exportaciones pesadas requieren Queued Jobs
- generación y descarga deben auditarse
- no implementar antes de estabilizar módulos base

## Milestones de implementación recomendados

### R1: Conversaciones listado read-only

Alcance:

- crear `ConversacionResource`
- navegación y permisos
- listado básico
- bloquear create/edit/delete

Validación:

- tests de acceso por rol
- tests de listado
- `make test`

### R2: Conversaciones filtros y búsqueda

Alcance:

- búsqueda por `uuid`, `wa_number`, `dni`
- filtros por canal, tipo de flujo, estado, activa y fechas

Validación:

- tests de búsqueda
- tests de filtros

### R3: Conversaciones detalle y timeline

Alcance:

- página de detalle
- mensajes read-only
- eventos read-only
- links a aviso/anticipo si existen

Validación:

- tests de detalle
- tests de orden de mensajes/eventos
- tests de no exposición de `payload_crudo` y metadata completa

### R4: Avisos read-only

Alcance:

- `AvisoResource`
- listado, búsqueda, filtros y detalle
- relaciones de conversación y certificados

Validación:

- tests de permisos
- tests de búsqueda/filtros
- tests de no exposición de `certificado_base64`

### R5: Anticipos/certificados read-only

Alcance:

- `AnticipoCertificadoResource`
- metadata de archivos sin links
- relaciones N a N y legacy visibles con cuidado

Validación:

- tests de permisos
- tests de archivo metadata
- tests de no links públicos ni `storage_path`

### R6: Auditoría read-only

Alcance:

- `AuditoriaAdministrativaResource`
- filtros por actor, action, origin y fecha
- detalle read-only

Validación:

- tests admin/auditor/director
- tests de filtros
- tests de no create/edit/delete

### R7: Dashboard mínimo

Alcance:

- widgets livianos
- permisos
- documentación de métricas

Validación:

- tests de render y métricas simples

## Decisiones pendientes

- `BO-002`: estrategia de storage privado de certificados.
- `BO-003`: operación manual de asociaciones aviso-certificado.
- `BO-004`: futuro de `anticipos_certificado.aviso_id`.
- Si avisos `observado` pueden recibir anticipo.
- Si se agregan permisos más finos como `*.viewAny` o se conserva la matriz inicial `*.view`.
- Si payload/metadata completa tendrá una vista técnica futura y con qué permiso.
- Cuándo incorporar Dusk o Playwright.

## Qué no implementar todavía

- `I4 - Storage privado de certificados`.
- descarga o visualización de archivos médicos.
- links públicos o temporary URLs.
- preview de certificados.
- cambios de estado de avisos o certificados.
- asociación/desasociación manual.
- reportes/exportaciones sensibles.
- Dusk o Playwright.
- permisos por campo/columna.
- State Machine.
- multi-tenancy o segmentación por sedes.
