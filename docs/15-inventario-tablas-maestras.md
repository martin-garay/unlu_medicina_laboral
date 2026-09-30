# Inventario de tablas maestras candidatas

Fecha: 2026-09-30. Backlog: [CAT-001](../plan_dev/BACKLOG.md).
Estado: C1, C3, C4 y C5 seleccionados e implementados como catálogos administrables;
C2 se mantiene como texto libre. C6–C17 conservan su análisis y no quedan aprobados.

## Alcance y evidencia

Revisión de `config/`, `lang/es/`, `resources/views/messages/`,
`resources/views/emails/`, handlers y validadores en `app/Flows/`, servicios,
modelos, Resources de Filament y migraciones. Se relevó código versionado;
no se consultaron datos reales de servidores ni se validó el catálogo institucional.
CAT-001 creó cuatro tablas con CRUD y semillas de compatibilidad; no creó ni verificó
el padrón institucional de sedes.

Fuente principal: [configuración de Medicina Laboral](../config/medicina_laboral.php).
La información en JSON de conversaciones/avisos es mayormente una instantánea
histórica de lo informado, no una tabla maestra pendiente de normalizar completa.

## A. Catálogos de negocio identificados

Sólo C1, C3, C4 y C5 se seleccionaron en CAT-001; los demás candidatos requieren
decisión separada.

| ID | Tabla candidata | Fuente y valores actuales | Consumidores / persistencia | Decisión necesaria |
| --- | --- | --- | --- | --- |
| C1 | `sedes` | Seed inicial conserva `central`, `campus`, `delegacion`; opciones previas del código, no padrón oficial | Validación/menú del chat; claves y snapshots de etiquetas | **DB + CRUD**. Falta importar la lista institucional completa; no cambiar códigos existentes. |
| C2 | — | El PDF especifica texto libre sin validación de catálogo | Identificación, avisos y anticipos | **Texto libre** por decisión del usuario; no se crea tabla ni opciones cerradas. |
| C3 | `tipos_ausentismo` | `por_enfermedad`, `atencion_familiar_enfermo`; capacidad `requiere_datos_familiar` | Validación/menú/handler y metadata del aviso | **DB + CRUD**. La capacidad del registro activa el subflujo familiar; el código es clave estable. |
| C4 | `tipos_certificado` | `manuscrito`, `electronico` | Validación/menú, metadata y anticipo | **DB + CRUD** como opción de negocio mantenida en admin y consumida por el chat. |
| C5 | `parentescos` | `madre`, `padre`, `hijo_hija`, `conyuge`, `otro` | Selección/validación familiar; clave y etiqueta snapshot | **DB + CRUD**; `otro` no exige datos adicionales. |

## B. Opciones y contenido administrable

Son candidatos adicionales; algunos son tablas de configuración o contenido y no
maestras de negocio. No implican aprobación de un editor de flujos.

| ID | Tablas candidatas | Evidencia actual | Alcance a decidir |
| --- | --- | --- | --- |
| C6 | `menus`, `menu_opciones` | `catalogos.menu_principal`, `mensajes.menu_principal_options`, `mensajes.menus`; acciones de confirmación en Blade/handlers | Texto, orden, visibilidad, ID de interacción y acción permitida. Derivar las opciones de C1–C5 desde su catálogo para no duplicar datos en dos tablas. |
| C7 | `opcion_aliases` / `comando_aliases` | Aliases del menú principal; `cancel_keywords`, `allowed_restart_keywords`, domicilio sí/no, adjuntar otro, omitir observaciones; aliases de confirmar/cancelar en handlers | Texto alternativo por opción/acción, ámbito y colisiones. Los números dependen del menú presentado; no deben reinterpretarse al reordenarlo. Puede resolverse como tabla dependiente, no como dos maestras obligatorias. |
| C8 | `mensajes`, `mensaje_versiones` | `lang/es/whatsapp.php`: preguntas, errores, avisos, cancelaciones, timeouts; `MessageResolver` | Clave estable, idioma, texto, variables permitidas, borrador/publicación y versión. Los textos de backoffice/chat local en `lang/es/backoffice.php` y `internal_chat.php` son otro alcance a decidir. |
| C9 | `plantillas_mensaje`, `plantilla_versiones` | `mensajes.templates`, `resources/views/messages/` y `resources/views/emails/aviso_registered.blade.php` | Resúmenes, mensajes finales, bienvenida, inactividad y correo. Evaluar si C8 y C9 comparten modelo. No ejecutar PHP/Blade arbitrario guardado en DB: definir placeholders y renderizado permitido. |
| C10 | `parametros_funcionales` (opcionalmente versiones/historial) | `conversation`, `avisos`, `certificados`: intentos, inactividad, longitudes, formatos de fecha, cantidad/tamaño de archivos y plazo de anticipo | Valores tipados, unidades/rangos, vigencia, permisos y caché. Alinear con [matriz de settings de certificados](backoffice/certificate-storage-settings.md) y BO-002; no duplicar esa iniciativa. |
| C11 | `formatos_adjunto_permitidos` | `allowed_extensions`, `allowed_mime_types`, `allowed_incoming_message_types`; validación/storage de adjuntos | Relación formato/extensiones/MIME, política por tipo de adjunto; los tipos de mensaje del proveedor pueden seguir en código. Alternativa: valores estructurados de C10, no crear ambas soluciones. |
| C12 | `notificaciones_configuracion` y/o `notificacion_destinatarios` | `mail.aviso_registered_recipient`, asunto y plantilla; `LaravelMailBusinessNotificationSender` | Administrar destino/asunto/habilitación por evento y canal. Hoy no hay evidencia de múltiples destinatarios ni de ruteo por sede; serían ampliaciones a definir. Credenciales/transporte permanecen fuera de estas tablas. |

## C. Dominios detectados que requieren otra decisión de alcance

| ID | Tablas posibles | Evidencia y recomendación para la selección |
| --- | --- | --- |
| C13 | `estados_aviso`, `estados_certificado` | Servicios/migraciones usan inicial; existe registrado histórico; docs de backoffice contemplan estados futuros. Evaluar catálogo de etiquetas/orden si será administrable; las transiciones y efectos siguen controlados por código. No sembrar estados sólo porque aparecen como propuesta documental. |
| C14 | `estados_vinculo`, `origenes_vinculo` | Pivot `anticipo_certificado_aviso`: activo, conversacion, legacy_aviso_id. Alinear con BO-003; probablemente códigos cerrados por ahora. La pivot ya existe. |
| C15 | `estados_validacion_archivo`, `motivos_rechazo` | `AnticipoCertificadoArchivo.estado_validacion/motivo_rechazo`, contratos de storage y plan BO-002 | Identificar valores reales vs estados futuros antes de crear catálogo. Mantener detalle del rechazo aunque se agregue un código normalizado. |
| C16 | `canales`, `tipos_flujo`, `pasos_flujo`, `estados_conversacion`, `tipos_evento`, `tipos_mensaje`, `motivos_cierre` | Constantes de Conversacion, registro de handlers, `stepKey`, acciones técnicas y trazabilidad | Dominios técnicos. No proponerlos como CRUD libre: dar de alta un canal/paso/evento no agrega su implementación. Considerar sólo etiquetas/reportes o un proyecto separado de motor configurable. |
| C17 | `trabajadores` o caché de identificación externa | `mapuche.mock.records`, `worker_identification.mock.records` | Son fixtures de una integración desacoplada, no padrón maestro validado. Definir fuente autoritativa Mapuche/local, sincronización y vigencia antes de replicar datos personales. |

## Datos que no requieren una nueva maestra por este relevamiento

- Nombre de trabajador/familiar, legajo, fechas, motivo, observaciones y domicilio
  son datos del trámite. No se detectó catálogo de enfermedades, domicilios,
  localidades o familiares: no inventarlo a partir de campos de texto libre.
- `metadata`, payloads, instantáneas de identificación, mensajes y eventos
  sostienen trazabilidad. Incorporar FKs no implica borrar estos datos históricos.
- Usuarios, roles, permisos y pivots de Spatie ya tienen tablas; la matriz en
  `config/backoffice.php` y su seeder son bootstrap, no ausencia de persistencia.
- Sí/no y confirmar/cancelar son acciones de navegación: si se administran,
  quedan en C6/C7, no necesitan una maestra diferente por cada pregunta.
- Drivers, rutas de archivos, tokens, credenciales, configuración de Laravel y
  parámetros de despliegue son configuración técnica; quedan fuera del alcance
  inicial de tablas maestras de negocio.

## Riesgos concretos encontrados

1. Los botones/validadores derivan de una consulta ordenada. Las conversaciones
   conservan una instantánea por catálogo; las respuestas inválidas la retienen
   hasta avanzar el paso.
2. Los mocks conservan valores históricos de jornada incompatibles con los antiguos
   catálogos; la jornada quedó como texto libre y esos valores no se normalizan.
3. Algunas selecciones se guardan como etiquetas en columnas y otras como claves
   en metadata. Inventariar valores reales antes del backfill; no mapear sólo por
   texto y no sobrescribir el nombre que se presentó en el trámite original.
4. WhatsApp limita listas a 10 opciones. Con más de 10 el chat muestra texto
   numerado y resuelve respuestas sobre la misma instantánea.
5. Ampliar sedes más allá de las tres opciones requiere una fuente institucional
   validada; la interfaz maneja catálogos largos con fallback numerado.

## Contrato implementado para las tablas elegidas

Tablas específicas con ID incremental, `codigo` único e inmutable, `nombre`, `activo`,
`orden` y timestamps. `tipos_ausentismo` agrega `requiere_datos_familiar`. Filament
permite alta/edición/desactivación con permisos diferenciados y auditoría; no permite
borrado. Cada selección conserva etiqueta snapshot en metadata.

Para cada candidata registrar: **a DB / permanece en código-config / diferida**,
responsable del dato, prioridad, relación/FK de destino, tratamiento de históricos,
administración requerida y dependencias. C6–C17 siguen sin selección.

## Próximos cortes y aceptación

1. Obtener el catálogo oficial de sedes y corroborar las opciones iniciales.
2. Revisar C6–C17 de manera independiente antes de cualquier otro CRUD.
3. Conservar snapshots y evaluar cambios de orden/baja durante conversaciones activas.
4. Validar automáticamente altas/bajas, código único, FK/backfill, orden,
   selección textual/interactiva, cambios durante una sesión, regresión completa
   de aviso/anticipo y permisos/auditoría. Ejecutar `make test` y checks del corte.
5. Validación manual: editar un catálogo desde la administración acordada y
   verificar nuevas sesiones e históricos en chat/WhatsApp/backoffice.

Stop de implementación: catálogo institucional o semántica sin definición,
históricos sin mapeo seguro, dependencia de Mapuche/BO-002 no resuelta o validación
obligatoria fallida. El relevamiento no habilita migraciones ni reemplazos masivos.
