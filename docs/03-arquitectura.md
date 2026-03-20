# Arquitectura

## Objetivo

Este documento describe la arquitectura inicial propuesta para el MVP de Medicina Laboral UNLu, con foco en:

- claridad de responsabilidades
- mantenibilidad
- extensibilidad
- trazabilidad
- separación entre integración, conversación y negocio

El objetivo es dejar una base técnica simple pero sólida para implementar los flujos conversacionales de:

- aviso de ausencia
- anticipo de certificado médico

## Diagramas relacionados

La referencia visual versionable de esta arquitectura vive en:

- `docs/diagrams/classes/conversation-engine.puml`
- `docs/diagrams/flows/aviso-ausencia.mmd`
- `docs/diagrams/flows/anticipo-certificado.mmd`

La convención de formatos está documentada en:

- `docs/diagrams/README.md`

## Principios arquitectónicos

### 1. Controllers livianos
Los controllers deben recibir requests y delegar trabajo.  
No deben contener la lógica principal del flujo.

### 2. Separación entre integración y dominio
La estructura del payload del proveedor externo no debe contaminar la lógica interna del sistema.

### 3. Conversación separada del negocio
La conversación guía y recopila datos.  
Las entidades de negocio se crean recién al finalizar correctamente el flujo.

### 4. Trazabilidad como requerimiento central
Toda interacción relevante debe quedar registrada.

### 5. Diseño incremental
La arquitectura debe permitir empezar simple y crecer sin rehacer todo.

---

## Vista general

La aplicación puede pensarse en cinco grandes capas o zonas de responsabilidad:

1. **Entrada / Integración**
2. **Motor de conversación**
3. **Flujos y validaciones**
4. **Persistencia y trazabilidad**
5. **Automatismos e integraciones futuras**

---

## 1. Entrada / Integración

## Responsabilidad

Recibir mensajes desde WhatsApp Cloud API, validarlos a nivel técnico básico y entregarlos al sistema interno en un formato utilizable.

## Componentes sugeridos

### `WhatsappWebhookController`
Responsabilidades:
- recibir requests GET/POST del webhook
- resolver verificación del webhook
- delegar procesamiento del mensaje entrante
- devolver respuestas HTTP adecuadas

No debería:
- contener validaciones del flujo
- crear avisos directamente
- tomar decisiones complejas de estados

### `WhatsAppSender`
Responsabilidades:
- enviar mensajes salientes a WhatsApp Cloud API
- encapsular detalles del proveedor
- registrar logs técnicos de envío

### Normalizador de entrada
Responsabilidad:
- transformar el payload crudo del proveedor en un formato interno estable

Aunque el patrón Adapter puede implementarse más adelante, la arquitectura ya debe dejar clara esta responsabilidad.

## Idea central

El resto del sistema no debería depender directamente de estructuras como:

- `entry[0].changes[0].value.messages[0]`

Esa lectura debe quedar encapsulada.

---

## 2. Motor de conversación

## Responsabilidad

Gestionar la sesión viva entre el usuario y el bot.

Debe poder:

- iniciar o recuperar una conversación
- determinar el paso actual
- registrar mensajes
- registrar eventos
- manejar intentos
- aplicar cancelación
- dejar trazabilidad
- preparar el contexto para avanzar el flujo

## Componentes sugeridos

### `ConversationManager`
Responsabilidades:
- obtener o crear conversación activa
- cerrar conversaciones
- actualizar timestamps
- incrementar contadores
- registrar cambios de estado
- asociar la conversación al resultado final cuando corresponda

### `ConversationMessageService`
Responsabilidades:
- persistir mensajes entrantes y salientes
- marcar validez
- registrar metadata
- asociar mensajes al paso actual

### `ConversationEventService`
Responsabilidades:
- registrar eventos técnicos y funcionales
- unificar trazabilidad no basada únicamente en mensajes

### Servicios desacoplados de notificación de negocio
Responsabilidades:
- encapsular futuros envíos de email u otras notificaciones
- permitir una implementación `null` por defecto
- evitar acoplar servicios de negocio a `Mail` o a un proveedor concreto

Implementación base sugerida:
- contrato `BusinessNotificationSender`
- implementación `NullBusinessNotificationSender`
- implementación opcional `LaravelMailBusinessNotificationSender`

### Servicios desacoplados de storage de adjuntos
Responsabilidades:
- capturar metadata del adjunto durante el borrador conversacional
- persistir referencias finales asociadas al anticipo materializado
- permitir evolución posterior a backend local, S3 u otro proveedor

Implementación base sugerida:
- contrato `DraftAttachmentStorage` para el paso conversacional
- contrato `FinalAttachmentStorage` para la persistencia final del anticipo
- implementación metadata-first mientras no exista descarga binaria definitiva

## Implementación base actual

En la etapa 2 se implementa una primera versión simple de estos servicios:

- `ConversationManager`
- `ConversationMessageService`
- `ConversationEventService`

Esta base:

- todavía no reemplaza el webhook actual
- no implementa una state machine completa
- no resuelve scheduler ni timeouts automáticos
- deja encapsuladas las operaciones mínimas de conversación para integrar en el siguiente bloque

### `ConversationStateMachine` o equivalente
Responsabilidades:
- definir transiciones de estado de la conversación
- evitar cambios inconsistentes
- centralizar reglas de transición

No es obligatorio implementarlo como state machine compleja al inicio, pero sí conviene tener una capa que ordene estas transiciones.

---

## 3. Flujos y validaciones

## Responsabilidad

Definir qué pasos existen, qué dato espera cada paso, cómo se valida y cómo avanza el proceso.

## Principio clave

No resolver todos los flujos con un único `switch` grande dentro del controller.

## Componentes sugeridos

### `ConversationFlowResolver`
Responsabilidades:
- identificar qué flujo está activo
- resolver el handler o componente que debe procesar el paso actual

### `StepHandler`
Responsabilidades:
- procesar un paso específico
- recibir el mensaje entrante
- validar el dato
- decidir la transición
- producir un resultado estructurado

### `Validator`
Responsabilidades:
- evaluar si el dato es válido
- devolver códigos de error estables
- no enviar mensajes ni persistir directamente

### Servicios desacoplados de identificación externa
Responsabilidades:
- encapsular la consulta de trabajador por legajo
- devolver datos básicos cuando estén disponibles
- permitir una implementación mock durante el MVP

Implementación inicial sugerida:
- contrato de aplicación `WorkerIdentificationService`
- implementación `MockWorkerIdentificationService` para desarrollo y tests
- adaptador `MapucheWorkerIdentificationService`
- contrato `MapucheWorkerProvider`
- implementación `MockMapucheWorkerProvider`

Evolución futura:
- implementación real contra Mapuche o API Mapuche sin acoplar handlers ni validadores al proveedor externo

Datos mínimos esperados de la integración futura:
- legajo
- nombre completo
- sede
- jornada laboral

La metadata del lookup puede persistirse en `metadata.identificacion.worker_lookup` como snapshot técnico del dato resuelto durante la conversación.

### `MessageResolver`
Responsabilidades:
- traducir resultados de validación a mensajes concretos
- obtener textos desde `lang/es/whatsapp.php`
- usar templates Blade cuando aplique

### `StepResult`
Responsabilidades:
- representar el resultado del procesamiento del paso

Puede contener:
- válido / inválido
- siguiente paso
- cambio de estado
- código de error
- mensaje a enviar
- si incrementa intentos
- si corresponde cancelar

## Implementación base del paso 2

La estructura mínima recomendada para empezar a desacoplar el controller queda compuesta por:

- `ConversationFlowResolver`
- contratos `StepHandler` y `Validator`
- `StepResult`
- `ValidationResult`
- `MessageResolver`

En esta etapa alcanza con uno o pocos handlers transicionales para empezar a mover ramas concretas del `switch` actual sin reescribir todavía todo el flujo.

## Subflujo inicial implementado

La entrada principal del chatbot queda modelada como un paso real del flujo:

- `menu_principal`

Desde ese paso el sistema puede:

- presentar bienvenida institucional y menú principal
- resolver selección de `consultas`, `aviso de ausencia` o `anticipo de certificado médico`
- enrutar transitoriamente a los pasos mínimos actuales de aviso o certificado
- volver al menú principal ante cancelación o reinicio básico del subflujo inicial

En esta fase, `consultas` puede permanecer visible pero responder como no disponible todavía.

## Bloque común de identificación

La identificación del trabajador se implementa como un subflujo compartido para `aviso` y `certificado`.

Pasos mínimos:

- `identificacion_nombre`
- `identificacion_legajo`
- `identificacion_sede`
- `identificacion_jornada`

La persistencia transitoria se resuelve en `metadata.identificacion` dentro de la conversación, evitando crear una tabla nueva en esta etapa.

---

## 4. Persistencia y trazabilidad

## Responsabilidad

Guardar la evidencia completa de lo ocurrido durante el flujo.

## Entidades principales

### `conversaciones`
Representa la sesión técnica del bot.

### `conversacion_mensajes`
Guarda cada mensaje entrante y saliente.

### `conversacion_eventos`
Guarda eventos relevantes del sistema y del flujo.

### `avisos`
Entidad de negocio generada al completar el flujo de aviso.

### `anticipos_certificado`
Entidad de negocio generada al completar el flujo de anticipo.

### `anticipo_certificado_archivos`
Adjuntos asociados al anticipo.

## Idea clave

La conversación debe existir y ser útil incluso cuando nunca se crea un aviso ni un anticipo.

---

## 5. Automatismos e integraciones futuras

## Responsabilidad

Resolver comportamientos que no dependen directamente de una interacción inmediata del usuario.

## Componentes sugeridos

### `Laravel Scheduler`
Responsabilidades:
- revisar conversaciones inactivas
- enviar recordatorios
- cancelar flujos automáticamente
- registrar eventos automáticos

### Jobs opcionales
Responsabilidades:
- desacoplar envíos
- mejorar escalabilidad futura
- permitir procesamiento asincrónico

### Servicios de integración desacoplados
Ejemplos:
- identificación del trabajador
- envío de emails
- integración con sistemas externos
- validaciones de aviso elegible
- almacenamiento de archivos

Puntos de extensión actuales del repo:
- `WorkerIdentificationService`
- `BusinessNotificationSender`
- `DraftAttachmentStorage`

---

## Flujo general de procesamiento

A nivel conceptual, el recorrido de un mensaje sería:

1. WhatsApp envía webhook
2. `WhatsappWebhookController` recibe request
3. se registra log técnico inicial si corresponde
4. se normaliza el mensaje entrante
5. `ConversationManager` obtiene o crea conversación
6. se registra mensaje entrante
7. se resuelve flujo y paso actual
8. se ejecuta handler del paso
9. se validan datos
10. se registran eventos y contadores
11. se genera respuesta
12. se registra mensaje saliente
13. `WhatsAppSender` envía respuesta
14. si el flujo termina correctamente:
    - se crea `Aviso` o `AnticipoCertificado`
    - se asocia a la conversación
    - se registra evento final

---

## Diagrama conceptual simple

```text
WhatsApp Cloud API
        |
        v
WhatsappWebhookController
        |
        v
Normalizador de entrada
        |
        v
ConversationManager
        |
        +------------------------------+
        |                              |
        v                              v
ConversationMessageService     ConversationEventService
        |
        v
ConversationFlowResolver
        |
        v
StepHandler actual
        |
        +-------------------+
        |                   |
        v                   v
Validator             MessageResolver
        |
        v
StepResult
        |
        v
WhatsAppSender
```

## Nota de mantenimiento

Cuando cambien relaciones estructurales importantes del motor conversacional, servicios base o contratos del flujo, también debe actualizarse el diagrama correspondiente en `docs/diagrams/`.
