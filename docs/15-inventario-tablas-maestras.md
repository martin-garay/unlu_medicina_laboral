# Inventario de tablas maestras candidatas

Fecha: 2026-09-27. Backlog: [CAT-001](../plan_dev/BACKLOG.md).
Estado: relevamiento completo del alcance indicado; selección pendiente con el usuario.
Los nombres de tablas son propuestas, no un esquema aprobado ni implementado.

## Alcance y evidencia

Revisión de `config/`, `lang/es/`, `resources/views/messages/`,
`resources/views/emails/`, handlers y validadores en `app/Flows/`, servicios,
modelos, Resources de Filament y migraciones. Se relevó código versionado;
no se consultaron datos reales de servidores ni se validó el catálogo institucional.
No se crearon tablas, migraciones, seeders ni pantallas administrativas.

Fuente principal: [configuración de Medicina Laboral](../config/medicina_laboral.php).
La información en JSON de conversaciones/avisos es mayormente una instantánea
histórica de lo informado, no una tabla maestra pendiente de normalizar completa.

## A. Catálogos de negocio identificados

Todas las filas quedan **por decidir**. Primera propuesta para discutir: empezar
por sedes y luego los demás catálogos de esta sección, previa definición de jornada.

| ID | Tabla candidata | Fuente y valores actuales | Consumidores / persistencia | Decisión necesaria |
| --- | --- | --- | --- | --- |
| C1 | `sedes` (o `centros_regionales`) | `catalogos.sedes`: central / Sede Central, campus / Campus Luján, delegacion / Delegación San Fernando; duplicado en `mensajes.menus.sedes` | `SedeValidator`, `IdentificacionSedeStepHandler`; clave/etiqueta en metadata, etiqueta en avisos y anticipos; filtros administrativos | Acordar nombre de la entidad, listado oficial y códigos externos. No asumir que estas tres opciones son todos los centros reales ni crear dos tablas para la misma entidad. |
| C2 | `jornadas_laborales` | `catalogos.jornadas_laborales`: planta_permanente / Planta permanente, parcial / Parcial; duplicado en menú; mocks usan Mañana/Tarde | `JornadaLaboralValidator`, handler de identificación, avisos y anticipos | Definir si representa jornada, turno o situación de revista. Separar entidades sólo si negocio confirma conceptos independientes. |
| C3 | `tipos_ausentismo` | `catalogos.tipos_ausentismo`: por_enfermedad, atencion_familiar_enfermo; botones y traducciones | `AusentismoTypeValidator`, handlers, `AvisoFamiliarService`, avisos | Descripción, orden, vigencia y eventual atributo `requiere_datos_familiar`. Hoy la clave activa lógica de handlers/servicios: una nueva fila no implementa automáticamente un nuevo flujo. |
| C4 | `tipos_certificado` | `catalogos.tipos_certificado`: manuscrito, electronico; botones y textos duplicados | `TipoCertificadoValidator`, handler de certificado, `AnticipoCertificadoService` | Confirmar catálogo y estabilidad de códigos; son categorías de certificado, distintas del formato PDF/JPG. |
| C5 | `parentescos` | `catalogos.parentescos` y `lang/es/whatsapp.php`: madre, padre, hijo_hija, conyuge, otro | `AvisoFamiliarService`, menú/lista y validación; clave en `metadata.aviso.parentesco` | Alta/baja/orden y etiquetas. Otro no exige datos adicionales según decisión vigente. |

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

1. Sedes/jornadas/tipos tienen catálogo y botones mantenidos por separado. Los
   validadores asocian ID de botón por posición: un orden distinto puede vincular
   opciones equivocadas. La fuente futura debe servir al menú y a la validación.
2. C2 mezcla conceptos distintos en mocks y menú. No normalizar esos valores a
   una FK sin acordar el significado; conservar valores históricos no mapeables.
3. Algunas selecciones se guardan como etiquetas en columnas y otras como claves
   en metadata. Inventariar valores reales antes del backfill; no mapear sólo por
   texto y no sobrescribir el nombre que se presentó en el trámite original.
4. Una baja o cambio de orden durante una conversación no debe invalidar ni
   reinterpretar su selección. Acordar versión/instantánea de opciones por paso.
5. Ampliar sedes puede requerir listas/paginación por los límites del canal;
   verificar menús, fallback y validación con el catálogo ampliado.

## Contrato propuesto para las tablas elegidas

Pendiente de acuerdo: ID interno, código estable único, etiqueta, activo,
orden y timestamps; código externo y vigencia sólo donde corresponda.
Definir permisos y auditoría de alta/edición/desactivación; no eliminar filas
referenciadas por trámites. La baja lógica es distinta de cambiar retrospectivamente
una instantánea. Evaluar tablas específicas vs catálogo genérico una vez elegidas
las entidades, sin diseñar una tabla universal como decisión implícita.

Para cada candidata registrar: **a DB / permanece en código-config / diferida**,
responsable del dato, prioridad, relación/FK de destino, tratamiento de históricos,
administración requerida y dependencias. Ninguna está seleccionada todavía.

## Próximos cortes y aceptación

1. Revisar C1–C17 con el usuario y cerrar la selección y semántica de C1/C2.
2. Diseñar sólo las tablas seleccionadas, acceso por servicio/interfaz, seed inicial,
   permisos, auditoría, caché y política para conversaciones en curso.
3. Promover implementación a un daily por autorización explícita. Migrar de forma
   incremental, conservar snapshots, actualizar modelo/diagramas y consumidores.
4. Validar automáticamente altas/bajas, código único, FK/backfill, orden,
   selección textual/interactiva, cambios durante una sesión, regresión completa
   de aviso/anticipo y permisos/auditoría. Ejecutar `make test` y checks del corte.
5. Validación manual: editar un catálogo desde la administración acordada y
   verificar nuevas sesiones e históricos en chat/WhatsApp/backoffice.

Stop de implementación: catálogo institucional o semántica sin definición,
históricos sin mapeo seguro, dependencia de Mapuche/BO-002 no resuelta o validación
obligatoria fallida. El relevamiento no habilita migraciones ni reemplazos masivos.
