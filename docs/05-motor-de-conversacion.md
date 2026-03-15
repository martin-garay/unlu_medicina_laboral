# Motor de conversación

## Objetivo

El motor de conversación es la base técnica que permite gestionar la interacción guiada entre el usuario y el chatbot de WhatsApp.

Su responsabilidad no es registrar directamente el aviso de ausencia ni el anticipo de certificado como entidades de negocio, sino sostener el proceso conversacional que eventualmente puede derivar en uno de esos registros.

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
- los textos todavía están hardcodeados
- no existe una state machine ni handlers por paso
- no hay scheduler de inactividad
- la trazabilidad de mensajes salientes se registra por intención de envío local, no por confirmación final del proveedor

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
