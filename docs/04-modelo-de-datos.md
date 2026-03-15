# Modelo de datos

## Objetivo

Este documento propone un modelo de datos inicial para el MVP de Medicina Laboral UNLu, alineado con las decisiones ya tomadas sobre:

- separación entre conversación y entidad de negocio
- trazabilidad completa
- soporte de cancelación e inactividad
- extensibilidad de flujos
- asociación entre aviso y anticipo de certificado

El objetivo no es cerrar definitivamente el esquema, sino definir una base consistente y mantenible para comenzar la implementación.

## Principios de modelado

### 1. Conversación y negocio son cosas distintas
La conversación representa una sesión técnica de interacción con el bot.  
El aviso y el anticipo de certificado representan entidades de negocio.

### 2. No se borra evidencia
Las conversaciones, mensajes y eventos no deben eliminarse por cancelación, error o inactividad.

### 3. Toda interacción debe ser trazable
Cada mensaje y cada evento relevante debe poder vincularse a una conversación.

### 4. El aviso se crea al final del flujo
La conversación recopila y valida datos; el aviso recién se materializa cuando el usuario confirma y el flujo finaliza correctamente.

### 5. El anticipo requiere aviso previo
El anticipo de certificado debe asociarse a un aviso existente y elegible.

---

## Entidades principales propuestas

Para esta primera etapa se proponen estas tablas:

- `conversaciones`
- `conversacion_mensajes`
- `conversacion_eventos`
- `avisos`
- `anticipos_certificado`
- `anticipo_certificado_archivos`

Además, podrían existir más adelante tablas auxiliares para catálogos o integraciones.

---

## 1. Tabla `conversaciones`

## Propósito

Representa la sesión viva o cerrada entre un usuario y el chatbot.

Cada conversación agrupa:

- mensajes entrantes y salientes
- estado del flujo
- métricas básicas
- timestamps de actividad
- eventos técnicos
- eventual asociación al resultado de negocio

## Campos sugeridos

- `id`
- `uuid`
- `wa_number`
- `canal`
- `tipo_flujo`
- `estado_actual`
- `paso_actual`
- `activa`
- `cantidad_mensajes_recibidos`
- `cantidad_mensajes_enviados`
- `cantidad_mensajes_validos`
- `cantidad_mensajes_invalidos`
- `cantidad_intentos_actual`
- `cantidad_intentos_totales`
- `ultimo_mensaje_recibido_en`
- `ultimo_mensaje_enviado_en`
- `primer_umbral_notificado_en`
- `segundo_umbral_notificado_en`
- `expira_en`
- `finalizada_en`
- `motivo_finalizacion`
- `aviso_id` nullable
- `anticipo_certificado_id` nullable
- `metadata` json nullable
- `created_at`
- `updated_at`

## Descripción conceptual de algunos campos

### `uuid`
Útil para referencias externas, logs o trazabilidad sin exponer IDs internos.

### `wa_number`
Número de WhatsApp del usuario.  
Es la clave principal para identificar de quién proviene la interacción.

### `canal`
Inicialmente podría ser siempre `whatsapp`, pero conviene dejar el campo preparado por si el sistema luego soporta otros canales.

### `tipo_flujo`
Ejemplos:
- `menu`
- `aviso_ausencia`
- `anticipo_certificado`

### `estado_actual`
Estado general de la conversación.

Ejemplos:
- `iniciada`
- `esperando_dato`
- `pendiente_confirmacion`
- `completada`
- `cancelada`
- `expirada`
- `error`

### `paso_actual`
Paso específico del flujo.

Ejemplos:
- `identificacion_nombre`
- `identificacion_legajo`
- `aviso_fecha_desde`
- `aviso_tipo_ausentismo`
- `certificado_tipo`
- `certificado_archivo`
- `confirmacion_final`

### `activa`
Indica si la conversación sigue disponible para continuar el flujo.

### `motivo_finalizacion`
Ejemplos:
- `completed`
- `user_cancelled`
- `inactivity_timeout`
- `max_attempts_exceeded`
- `system_error`

### `metadata`
Campo flexible para guardar información contextual adicional sin tener que cambiar el esquema ante pequeños ajustes.

## Índices sugeridos

- índice por `wa_number`
- índice por `activa`
- índice por `tipo_flujo`
- índice por `estado_actual`
- índice por `paso_actual`
- índice por `ultimo_mensaje_recibido_en`

## Consideraciones

- no debe existir más de una conversación activa del mismo tipo para el mismo usuario, salvo que negocio lo permita explícitamente
- las restricciones exactas pueden resolverse con lógica de aplicación o con restricciones parciales según el motor de base

---

## 2. Tabla `conversacion_mensajes`

## Propósito

Registrar cada mensaje entrante o saliente asociado a una conversación.

Esto incluye:

- mensajes del usuario
- respuestas automáticas del sistema
- menús
- mensajes de error
- recordatorios por inactividad
- confirmaciones finales

## Campos sugeridos

- `id`
- `uuid`
- `conversacion_id`
- `direccion`
- `provider_message_id` nullable
- `tipo_mensaje`
- `step_key` nullable
- `contenido_texto` nullable
- `es_valido` nullable
- `motivo_invalidez` nullable
- `message_key` nullable
- `template_name` nullable
- `payload_crudo` json nullable
- `metadata` json nullable
- `created_at`
- `updated_at`

## Descripción conceptual

### `direccion`
Valores esperables:
- `in`
- `out`

### `provider_message_id`
Identificador externo del mensaje si el proveedor lo entrega.  
Por ejemplo, `wamid` en WhatsApp.

### `tipo_mensaje`
Ejemplos:
- `text`
- `button`
- `interactive`
- `image`
- `document`
- `system`

### `step_key`
Paso del flujo al que corresponde el mensaje.

### `es_valido`
Se usa principalmente para mensajes entrantes cuando el sistema puede determinar si la entrada fue válida para el paso actual.

En mensajes salientes puede quedar null o utilizarse solo si tiene sentido técnico.

### `motivo_invalidez`
Ejemplos:
- `required`
- `invalid_option`
- `invalid_date`
- `invalid_attachment_type`

### `message_key`
Clave del archivo de idioma utilizada para construir el mensaje, cuando aplique.

### `template_name`
Nombre del template Blade usado, cuando el mensaje venga de una vista.

### `payload_crudo`
Permite auditar el contenido original recibido o enviado.

## Índices sugeridos

- índice por `conversacion_id`
- índice por `provider_message_id`
- índice por `step_key`
- índice por `created_at`

## Consideraciones

- esta tabla es central para métricas, debugging y auditoría
- no debe depender de la estructura del payload del proveedor para ser útil

---

## 3. Tabla `conversacion_eventos`

## Propósito

Registrar eventos técnicos y funcionales relevantes de una conversación, sin necesidad de inferirlos solo a partir de mensajes.

Esto permite dejar trazabilidad más rica y explícita.

## Campos sugeridos

- `id`
- `uuid`
- `conversacion_id`
- `tipo_evento`
- `step_key` nullable
- `descripcion` nullable
- `codigo` nullable
- `metadata` json nullable
- `created_at`
- `updated_at`

## Ejemplos de `tipo_evento`

- `conversation_started`
- `step_changed`
- `validation_failed`
- `retry_incremented`
- `timeout_warning_1`
- `timeout_warning_2`
- `conversation_cancelled`
- `conversation_cancelled_by_inactivity`
- `conversation_completed`
- `aviso_created`
- `anticipo_created`
- `attachment_rejected`
- `max_attempts_exceeded`

## Ejemplos de `codigo`

- `required`
- `invalid_option`
- `invalid_date`
- `user_cancelled`
- `inactivity_timeout`

## Índices sugeridos

- índice por `conversacion_id`
- índice por `tipo_evento`
- índice por `step_key`
- índice por `created_at`

## Consideraciones

- esta tabla es especialmente útil para auditoría y monitoreo
- ayuda a separar “mensaje enviado” de “evento del sistema”

---

## 4. Tabla `avisos`

## Propósito

Representa el aviso de ausencia como entidad de negocio.

No se crea al iniciar el flujo, sino al finalizarlo correctamente.

## Campos sugeridos

- `id`
- `uuid`
- `numero_aviso`
- `conversacion_id`
- `wa_number`
- `nombre_completo`
- `legajo`
- `sede`
- `jornada_laboral`
- `fecha_desde`
- `fecha_hasta`
- `tipo_ausentismo`
- `motivo`
- `domicilio_circunstancial` nullable
- `observaciones` nullable
- `datos_familiar` json nullable
- `estado`
- `registrado_en`
- `created_at`
- `updated_at`

## Descripción conceptual

### `numero_aviso`
Identificador visible o funcional del aviso.  
Puede ser distinto del `id`.

### `conversacion_id`
Relaciona el aviso con la conversación que lo generó efectivamente.

### `datos_familiar`
Permite almacenar información adicional cuando el tipo de ausentismo lo requiera.

Ejemplo:
- nombre del familiar
- parentesco
- observaciones específicas

### `estado`
Ejemplos:
- `pendiente`
- `registrado`
- `a_validar`
- `invalidado`
- `cancelado`

La definición final deberá alinearse con el módulo administrativo futuro.

## Índices sugeridos

- índice por `numero_aviso`
- índice por `legajo`
- índice por `wa_number`
- índice por `estado`
- índice por `fecha_desde`
- índice por `fecha_hasta`
- índice por `conversacion_id`

## Consideraciones

- un aviso debe quedar asociado a una única conversación efectiva
- conversaciones canceladas o fallidas no deben generar aviso

---

## 5. Tabla `anticipos_certificado`

## Propósito

Representa el anticipo de certificado médico como entidad de negocio.

Debe quedar asociado a:

- una conversación efectiva
- un aviso previo

## Campos sugeridos

- `id`
- `uuid`
- `numero_anticipo`
- `conversacion_id`
- `aviso_id`
- `wa_number`
- `nombre_completo`
- `legajo`
- `sede`
- `jornada_laboral`
- `tipo_certificado`
- `estado`
- `observaciones` nullable
- `registrado_en`
- `created_at`
- `updated_at`

## Descripción conceptual

### `aviso_id`
Clave central para vincular el anticipo al aviso previo.

### `tipo_certificado`
Inicialmente puede resolverse por config o enum.  
Más adelante podría migrarse a catálogo administrable.

### `estado`
Ejemplos:
- `pendiente`
- `registrado`
- `a_validar`
- `vinculado`
- `invalidado`

## Índices sugeridos

- índice por `numero_anticipo`
- índice por `aviso_id`
- índice por `legajo`
- índice por `wa_number`
- índice por `estado`
- índice por `conversacion_id`

## Consideraciones

- no debe existir anticipo válido sin aviso asociado
- la regla de elegibilidad del aviso debe resolverse en la capa de aplicación y/o validaciones de negocio

---

## 6. Tabla `anticipo_certificado_archivos`

## Propósito

Registrar los archivos adjuntos asociados al anticipo de certificado.

Se separa en una tabla propia para soportar múltiples archivos por anticipo.

## Campos sugeridos

- `id`
- `uuid`
- `anticipo_certificado_id`
- `conversacion_id`
- `provider_file_id` nullable
- `nombre_original` nullable
- `mime_type`
- `extension` nullable
- `size_bytes` nullable
- `storage_disk` nullable
- `storage_path` nullable
- `hash_archivo` nullable
- `estado_validacion`
- `motivo_rechazo` nullable
- `metadata` json nullable
- `created_at`
- `updated_at`

## Descripción conceptual

### `provider_file_id`
Id técnico del proveedor si existe.

### `storage_disk` y `storage_path`
Preparan el terreno para cuando el archivo se almacene en un disco local, S3 u otra estrategia de storage.

### `estado_validacion`
Ejemplos:
- `pendiente`
- `aceptado`
- `rechazado`

### `motivo_rechazo`
Ejemplos:
- `invalid_attachment_type`
- `attachment_too_large`
- `unreadable_file`

## Índices sugeridos

- índice por `anticipo_certificado_id`
- índice por `conversacion_id`
- índice por `estado_validacion`

---

## Relaciones principales

## Conversación → mensajes
Una conversación tiene muchos mensajes.

## Conversación → eventos
Una conversación tiene muchos eventos.

## Conversación → aviso
Una conversación puede generar cero o un aviso.

## Conversación → anticipo
Una conversación puede generar cero o un anticipo.

## Aviso → anticipos
Un aviso puede tener cero o muchos anticipos, según la regla funcional que finalmente se adopte.

## Anticipo → archivos
Un anticipo puede tener uno o muchos archivos.

---

## Diagrama conceptual simple

```text
conversaciones
  ├── conversacion_mensajes
  ├── conversacion_eventos
  ├── aviso (0..1)
  └── anticipo_certificado (0..1)

avisos
  └── anticipos_certificado (0..n)

anticipos_certificado
  └── anticipo_certificado_archivos (1..n)