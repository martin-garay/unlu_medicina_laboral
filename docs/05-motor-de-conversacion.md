# Motor de conversación

## Objetivo

El motor de conversación es la base técnica que permite gestionar la interacción guiada entre el usuario y el chatbot de WhatsApp.

Su responsabilidad no es registrar directamente el aviso de ausencia ni el anticipo de certificado como entidades de negocio, sino sostener el proceso conversacional que eventualmente puede derivar en uno de esos registros.

## Diagramas relacionados

La referencia visual versionable del motor y sus flujos vive en:

- `docs/diagrams/classes/conversation-engine.puml`
- `docs/diagrams/flows/aviso-ausencia.mmd`
- `docs/diagrams/flows/anticipo-certificado.mmd`

La convención general está en:

- `docs/diagrams/README.md`

## Idea central

El proyecto debe separar claramente tres conceptos:

- **Conversación**: unidad técnica y trazable de interacción
- **Aviso**: entidad de negocio generada al finalizar correctamente un flujo de aviso de ausencia
- **Anticipo de certificado**: entidad de negocio generada al finalizar correctamente un flujo de anticipo y vinculada a un aviso previo

Esta separación es fundamental para mantener la trazabilidad, evitar inconsistencias y soportar cancelaciones, errores, reintentos e inactividad sin contaminar los registros de negocio.

## Responsabilidades del motor de conversación

El motor de conversación debe poder:

- iniciar una nueva conversación
- recuperar una conversación activa
- determinar en qué paso del flujo se encuentra el usuario
- registrar cada mensaje entrante y saliente
- identificar mensajes válidos e inválidos
- contar intentos por paso y totales
- mantener timestamps de actividad
- aplicar reglas de timeout e inactividad
- permitir cancelación manual del flujo
- cerrar la conversación por cancelación o expiración
- dejar trazabilidad suficiente para auditoría técnica
- habilitar la materialización posterior del aviso o anticipo

## Base implementada en esta etapa

La base mínima del motor queda apoyada en tres servicios:

- `ConversationManager`: obtiene o crea conversaciones activas, actualiza timestamps, incrementa contadores y permite cerrarlas sin borrarlas
- `ConversationMessageService`: registra mensajes entrantes y salientes asociados a una conversación
- `ConversationEventService`: registra eventos técnicos mínimos para trazabilidad

Esta implementación es deliberadamente simple y no reemplaza todavía la lógica actual del webhook ni modela transiciones complejas de estado.

## Integración incremental con el webhook actual

En el tercer bloque de la etapa 2 el webhook existente pasa a usar esta base para:

- buscar o crear la conversación activa por `wa_number`
- registrar cada mensaje entrante en `conversacion_mensajes`
- registrar cada mensaje saliente en `conversacion_mensajes`
- registrar eventos mínimos en `conversacion_eventos`
- actualizar contadores básicos en `conversaciones`

La lógica funcional vigente del bot se mantiene en forma transitoria, aunque todavía no está desacoplada en handlers por paso.

## Validación manual de la Etapa 2

Checklist sugerido para validar la base del motor de conversación:

1. levantar el entorno con Docker
2. ejecutar migraciones sobre una base limpia
3. exponer el webhook local con `ngrok`
4. enviar un primer mensaje desde WhatsApp y verificar:
   - creación de una `conversacion`
   - evento `conversation_started`
   - mensaje entrante persistido en `conversacion_mensajes`
5. avanzar por el flujo actual y verificar:
   - incremento de contadores en `conversaciones`
   - eventos `incoming_message_received` y `outgoing_message_sent`
   - persistencia de mensajes salientes `text` e `interactive`
6. completar un aviso o certificado y verificar:
   - la conversación queda cerrada
   - `activa = false`
   - `finalizada_en` y `motivo_finalizacion` completos
7. enviar un nuevo mensaje luego de una conversación cerrada y verificar:
   - creación de una nueva conversación
   - no reutilización de la anterior

Consulta útil desde `php artisan tinker`:

```php
App\Models\Conversacion::with(['mensajes', 'eventos'])->latest('id')->first();
```

## Limitaciones actuales al cierre de Etapa 2

- el webhook sigue conteniendo la lógica funcional transitoria del MVP
- aún conviven campos legacy (`estado`, `tipo`, `dni`) con los nuevos (`estado_actual`, `paso_actual`, `tipo_flujo`)
- todavía conviven mensajes centralizados con algunas salidas transitorias del MVP
- no existe una state machine completa y solo parte del flujo está migrado a handlers por paso
- no hay scheduler de inactividad
- la trazabilidad de mensajes salientes se registra por intención de envío local, no por confirmación final del proveedor

## Avance esperado en el refactor gradual

En el siguiente paso del refactor conviene mover progresivamente más estados reales del webhook hacia handlers concretos, haciendo que:

- `StepResult` gobierne transiciones, finalización y cancelación
- `MessageResolver` resuelva la mayor parte de los mensajes de salida
- el controller quede concentrado en orquestar efectos técnicos

## Estado actual de desacople respecto del canal

El estado real del repo ya muestra una separación parcial entre:

- núcleo conversacional reusable
- adapters específicos del canal WhatsApp

### Piezas que ya están bastante desacopladas del canal

Estas piezas pueden reutilizarse con cambios relativamente menores para un canal interno o alternativo:

- `ConversationManager`
- `ConversationMessageService`
- `ConversationEventService`
- `ConversationFlowResolver`
- `StepResult`
- `MessageResolver`
- handlers por paso
- validadores
- `ConversationContextService`
- servicios de materialización de `Aviso` y `AnticipoCertificado`

La razón principal es que el núcleo del flujo ya trabaja sobre:

- conversación persistida
- estado/paso actual
- metadata acumulada
- un input normalizado como array
- un `StepResult` que describe transición, mensaje y efectos

Esto significa que la lógica de negocio principal no depende de `Illuminate\Http\Request` ni del payload crudo de Meta una vez superada la capa de entrada.

### Piezas que siguen acopladas a WhatsApp

El acople más fuerte todavía vive en los bordes de entrada y salida.

#### Entrada

`WhatsappWebhookController` hoy:

- interpreta directamente el payload de Meta
- extrae `from`, `text`, `interactive.button_reply`, `document`, `image`
- define `incoming_message_type`
- arma metadata de adjuntos con claves como `provider_media_id`
- asume que el identificador principal del usuario es `wa_number`

Ese controller ya normaliza parte de la entrada, pero la normalización sigue embebida en un adapter concreto de WhatsApp.

#### Salida

La respuesta saliente hoy también queda acoplada al canal porque:

- el controller llama directamente a `WhatsAppSender`
- el menú principal se emite como `interactive` de WhatsApp
- la trazabilidad saliente se registra en el controller con payloads que reflejan la forma del canal WhatsApp
- `ConversationTimeoutService` también depende directamente de `WhatsAppSender`

#### Modelo de conversación

La conversación conserva hoy un sesgo de canal en algunos campos:

- `wa_number`
- `canal` con default WhatsApp

Eso no bloquea sumar otro canal, pero obliga a definir una estrategia de identidad/canal antes de generalizar demasiado.

### Qué tan reutilizable es el flujo actual

La reutilización para otro canal es viable, pero no inmediata de punta a punta.

Conclusión práctica:

- el motor conversacional y los handlers ya permiten reutilización real
- el webhook y el envío saliente todavía funcionan como adapter único y dominante
- hoy sería razonable abrir un segundo canal sin reescribir los handlers
- no sería razonable hacerlo sin antes extraer una capa de orquestación de canal mínima

## Recomendación mínima para soportar un canal interno

La estrategia recomendada no es reescribir el motor, sino aislar mejor los adapters.

### 1. DTO interno de entrada

Crear un objeto o estructura interna equivalente a:

- `channel`
- `user_id`
- `text`
- `action_id`
- `incoming_message_type`
- `media`
- `provider_message_id`
- `raw_payload`

Ese DTO debe representar una interacción entrante independientemente de si vino de:

- WhatsApp webhook
- consola web interna
- endpoint HTTP local de prueba

### 2. Servicio de orquestación de interacción

Conviene extraer del controller una pieza tipo:

- `ConversationInteractionService`
- o `ConversationEngine`

Responsabilidades:

- obtener o crear conversación activa
- normalizar aplicación de `StepResult`
- registrar mensajes y eventos
- ejecutar acción de negocio si corresponde
- devolver una respuesta saliente estructurada

Así, `WhatsappWebhookController` pasaría a ser solo un adapter de transporte.

### 3. Abstracción de salida por canal

Conviene introducir una interfaz de salida, por ejemplo:

- `ConversationChannelSender`

Con implementaciones como:

- `WhatsAppChannelSender`
- `LocalChatChannelSender`

Esto permitiría que:

- el controller no conozca detalles del provider
- `ConversationTimeoutService` deje de depender de `WhatsAppSender`
- un canal interno pueda renderizar texto y opciones sin usar payloads de Meta

### 4. Salida estructurada del motor

En vez de que el controller decida si manda texto o menú interactivo con lógica específica de WhatsApp, la capa de aplicación debería devolver algo como:

- mensaje de texto
- menú/opciones
- metadatos de trazabilidad

Luego cada adapter decide cómo representarlo:

- botones interactivos en WhatsApp
- lista clickable en consola interna
- texto numerado como fallback

## Esfuerzo estimado

Para una primera consola local o canal interno mínimo:

- esfuerzo `low-medium` si el objetivo es texto + opciones numeradas + reutilización de handlers actuales
- esfuerzo `medium` si además se quiere paridad con adjuntos, menú rico y storage equivalente al flujo de certificado

## Riesgos y límites actuales

- el menú principal todavía tiene una salida optimizada para botones interactivos de WhatsApp, aunque ya acepta texto y aliases
- la identidad del usuario sigue modelada principalmente alrededor de `wa_number`
- la trazabilidad saliente registra hoy payloads cercanos al canal real, no una representación canónica independiente del transporte
- `ConversationTimeoutService` comparte el mismo acople de salida que el webhook

## Conclusión operativa

El repo ya está razonablemente preparado para sumar otro canal sin reescribir el dominio conversacional.

El cuello de botella no está en los handlers ni en `StepResult`.
Está en que:

- entrada
- salida
- y parte de la orquestación de mensajes

todavía viven acopladas a WhatsApp.

El siguiente paso correcto no es construir directamente una consola completa, sino extraer primero una capa de interacción multicanal mínima sobre el motor actual.

## Menú principal conversacional

La conversación nueva puede iniciar en `menu_principal` y usar un handler específico para:

- presentar bienvenida institucional y menú
- aceptar selección de flujo
- dejar trazado el flujo elegido en la conversación
- permitir cancelación o reinicio básico hacia el menú principal

En esta etapa, volver al menú principal no cierra la conversación: reinicia el subflujo dentro de la misma sesión técnica para no perder trazabilidad y para mantener el cambio acotado.

## Identificación común reutilizable

Para los flujos principales de aviso y anticipo, la conversación puede pasar por un bloque común de identificación que:

- solicita nombre, legajo, sede y jornada laboral
- valida mínimamente cada dato
- persiste el borrador en `metadata.identificacion`
- al completarse, deja la conversación encaminada al próximo paso del flujo elegido

Esto permite reutilizar la captura de identidad sin duplicar handlers por flujo.

## Borrador transitorio de aviso

Mientras el aviso no se confirma ni se materializa, la conversación puede almacenar un borrador bajo `metadata.aviso`.

Ejemplos de claves:

- `metadata.aviso.fecha_desde`
- `metadata.aviso.fecha_hasta`
- `metadata.aviso.tipo_ausentismo`
- `metadata.aviso.motivo`
- `metadata.aviso.informo_domicilio_circunstancial`
- `metadata.aviso.domicilio_circunstancial`
- `metadata.aviso.observaciones`

Cuando el usuario confirma el resumen final del aviso, ese borrador deja de ser solo contexto conversacional y puede materializarse en un `Aviso` real mediante un servicio específico, manteniendo desacoplado al controller de la persistencia de negocio.

## Borrador transitorio de anticipo de certificado

Antes de crear un anticipo de certificado real, la conversación puede almacenar un borrador bajo `metadata.certificado`.

Ejemplos de claves:

- `metadata.certificado.aviso_id`
- `metadata.certificado.numero_aviso`
- `metadata.certificado.tipo_certificado`
- `metadata.certificado.tipo_certificado_label`
- `metadata.certificado.adjuntos`

## Estado actual del flujo de anticipo

La base implementada llega hoy hasta:

- identificación común
- validación de aviso previo
- selección de tipo de certificado
- captura de metadata mínima de un adjunto

La confirmación final y la materialización del anticipo siguen pendientes. Los diagramas del directorio `docs/diagrams/` deben reflejar ese alcance real y no una implementación hipotética.

## Qué no debe hacer

El motor de conversación no debe asumir que toda conversación termina exitosamente ni que toda interacción se traduce en un registro válido.

Tampoco debe mezclar:

- lógica de persistencia de mensajes
- lógica de validación de cada paso
- lógica de creación de entidades de negocio
- textos hardcodeados de respuesta

## Entidades conceptuales

## 1. Conversación

Representa una sesión viva entre un usuario y el sistema.

Debe incluir al menos:

- número de WhatsApp
- canal
- tipo de flujo actual
- estado actual
- timestamps relevantes
- indicadores de actividad
- contadores
- relación con mensajes
- relación con eventos
- eventual relación con aviso o anticipo materializado

### Datos mínimos sugeridos

- identificador de conversación
- `wa_number`
- `canal`
- `tipo_flujo`
- `estado_actual`
- `activa`
- `cantidad_mensajes_recibidos`
- `cantidad_mensajes_validos`
- `cantidad_mensajes_invalidos`
- `cantidad_intentos_actual`
- `ultimo_mensaje_recibido_en`
- `primer_umbral_notificado_en`
- `segundo_umbral_notificado_en`
- `expira_en`
- `finalizada_en`
- `motivo_finalizacion`
- `metadata`

## 2. Mensajes de conversación

Cada interacción debe quedar persistida como mensaje asociado a una conversación.

Esto incluye:

- mensajes entrantes del usuario
- mensajes salientes enviados por el sistema

### Datos sugeridos

- `conversacion_id`
- dirección (`in` / `out`)
- tipo de mensaje
- contenido textual
- payload crudo
- indicador de validez
- motivo de invalidez
- paso del flujo al que corresponde
- fecha y hora de registro

## 3. Eventos de conversación

Además de mensajes, deben registrarse eventos técnicos y funcionales relevantes.

Ejemplos:

- cambio de estado
- cambio de paso
- validación fallida
- superación de intentos
- recordatorio por inactividad
- cancelación por inactividad
- cancelación manual
- creación de aviso
- creación de anticipo

Los eventos permiten reconstruir técnicamente lo ocurrido sin depender solo del historial de mensajes.

## Ciclo de vida de una conversación

Una conversación puede:

- iniciarse
- avanzar por uno o más pasos
- recibir entradas válidas o inválidas
- quedar pausada por falta de respuesta
- recibir recordatorios automáticos
- cancelarse manualmente
- cancelarse por inactividad
- finalizar exitosamente
- derivar en un aviso
- derivar en un anticipo de certificado
- cerrarse sin generar ninguna entidad de negocio

## Cancelación

## Regla acordada

No se deben borrar físicamente las conversaciones ni los mensajes.

Cuando una conversación se cancela:

- se marca como finalizada
- deja de estar activa
- conserva todos sus mensajes y eventos
- no debe reutilizarse para un flujo nuevo
- no debe mezclarse con una nueva interacción posterior

Si el usuario vuelve a iniciar el proceso luego de cancelar, debe generarse una nueva conversación.

### Consecuencia de diseño

Los mensajes previos a una cancelación deben seguir asociados a la conversación cancelada.

Los mensajes posteriores de un nuevo intento deben pertenecer a una nueva conversación.

Esto garantiza trazabilidad correcta y evita mezclar intentos distintos bajo un mismo flujo lógico.

## Inactividad

El sistema debe soportar automatismos por inactividad.

### Casos previstos

- primer umbral de inactividad: recordatorio automático
- segundo umbral de inactividad: aviso de cancelación próxima o cancelación
- cancelación automática del flujo
- registro técnico de estos eventos

### Implementación sugerida

Usar **Laravel Scheduler** para ejecutar tareas periódicas que revisen conversaciones activas y detecten:

- conversaciones con recordatorio pendiente
- conversaciones que deben cancelarse por inactividad
- eventos técnicos a registrar

### Qué registrar al cancelar por inactividad

Como mínimo:

- fecha y hora
- teléfono
- tipo de evento
- etapa del flujo
- último estado conocido
- cantidad de intentos
- datos técnicos relevantes

## Mensajes válidos e inválidos

El motor de conversación debe registrar:

- cuántos mensajes recibió el usuario durante el flujo
- cuáles fueron válidos
- cuáles fueron inválidos
- en qué paso fallaron
- por qué motivo

Esto es importante por dos razones:

1. trazabilidad funcional y técnica
2. métricas del flujo conversacional

Ejemplos de causas de invalidez:

- dato vacío
- formato incorrecto
- opción inexistente
- fecha inválida
- valor fuera de rango
- intento de registrar certificado sin aviso previo
- superación de cantidad máxima de intentos

## Extensibilidad de validaciones y respuestas

La implementación debe permitir que cada paso del flujo tenga su propia lógica sin concentrar todo en un único controller o en un `switch` grande difícil de mantener.

Cada paso debería poder definir:

- dato esperado
- validación
- respuesta si es válido
- respuesta si es inválido
- cantidad de intentos permitidos
- transición al siguiente paso
- eventos asociados

Esto permitirá escalar los flujos sin degradar la mantenibilidad.

## Base estructural para el siguiente refactor

Antes de implementar los flujos reales completos conviene dejar creada una base mínima con:

- un `ConversationFlowResolver`
- handlers por paso
- `StepResult` para expresar el resultado del procesamiento
- `ValidationResult` para desacoplar la validación
- un `MessageResolver` para traducir claves y templates a mensajes concretos

Esto permite migrar el webhook de forma gradual, rama por rama, sin romper el MVP actual.

## Mensajes y parámetros

## Textos

Los textos no deben quedar hardcodeados.

Se utilizarán archivos de idioma de Laravel, por ejemplo:

- `lang/es/whatsapp.php`

## Parámetros

Las reglas configurables deben moverse a archivos de configuración, por ejemplo:

- `config/medicina_laboral.php`

Ejemplos de parámetros:

- cantidad máxima de intentos
- umbrales de inactividad
- formatos permitidos de archivos
- tamaño máximo de archivo
- plazo permitido para anticipo de certificado
- palabras clave de cancelación

## Templates

Los mensajes largos, especialmente confirmaciones finales, deben renderizarse desde templates Blade para facilitar:

- reutilización
- parametrización
- futura administración desde backoffice

## Integraciones futuras

La identificación real del trabajador y otras validaciones contra sistemas externos no deben acoplarse directamente al flujo conversacional.

La recomendación es encapsular estas integraciones detrás de servicios o interfaces, permitiendo usar implementaciones mock durante las primeras etapas.

## Resultado esperado de esta etapa

Se considerará que el motor de conversación está correctamente establecido cuando el sistema pueda:

- mantener una conversación activa por usuario
- registrar todos los mensajes asociados
- distinguir mensajes válidos e inválidos
- contar intentos
- cerrar conversaciones sin borrarlas
- cancelar por inactividad usando Scheduler
- separar conversación de entidad de negocio
- dejar la base lista para implementar los flujos de aviso y anticipo
### Refactor gradual del webhook

El webhook ya no debería concentrar la resolución manual del flujo. La base actual apunta a que:

- `ConversationFlowResolver` elija el handler según `paso_actual`
- cada `StepHandler` procese el input y devuelva un `StepResult`
- `MessageResolver` traduzca ese resultado a texto o template
- el controller solo orqueste trazabilidad, transición, cierre y envío

En la fase transicional actual, el flujo mínimo cubre:

- `esperando_dni`
- `esperando_tipo`
- `esperando_cantidad_dias`
- `esperando_certificado`
- fallback para estados no soportados

Los nombres y mensajes marcados como `transicional` existen para sostener el MVP vigente mientras se desacopla el flujo real de aviso y certificado.

## Nota de mantenimiento

Si cambian pasos, contratos, handlers relevantes o el alcance real de los flujos, actualizar también los diagramas correspondientes en `docs/diagrams/`.
