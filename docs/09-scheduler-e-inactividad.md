# Scheduler e inactividad

## Objetivo

Este documento define cómo implementar los automatismos vinculados a inactividad en el sistema conversacional de Medicina Laboral UNLu.

El objetivo es que el sistema pueda:

- detectar conversaciones inactivas
- enviar recordatorios automáticos
- aplicar un segundo umbral configurable
- cancelar automáticamente un flujo por inactividad
- registrar técnicamente todos estos eventos

## Motivación funcional

La documentación funcional establece comportamientos automáticos frente a la falta de respuesta del usuario, incluyendo:

- recordatorio automático
- segundo umbral de inactividad
- cancelación automática del flujo
- registro técnico del evento

Estos comportamientos no deben depender del webhook de entrada, porque justamente ocurren cuando el usuario **no envía** mensajes.

## Enfoque recomendado

Usar **Laravel Scheduler** como mecanismo principal de revisión periódica de conversaciones activas.

## Razones

- se integra naturalmente con Laravel
- centraliza automatismos
- facilita pruebas y mantenimiento
- permite separar lógica conversacional de procesos temporales
- evita cargar esta responsabilidad en el webhook

## Arquitectura sugerida

## Componentes principales

### 1. Scheduler
Ejecuta tareas periódicas.

### 2. Comando o servicio de revisión
Busca conversaciones activas que hayan superado ciertos umbrales.

### 3. Jobs opcionales
Pueden utilizarse para enviar mensajes asincrónicos si se desea desacoplar la ejecución.

### Estado actual

En el estado actual del proyecto, los envíos automáticos siguen ejecutándose inline desde el comando schedulerizado.

Esto se mantiene así porque:

- la carga esperada del MVP sigue siendo baja
- incorporar colas ahora agregaría complejidad operativa sin un cuello de botella comprobado
- el punto de evolución hacia jobs queda identificado para una etapa posterior

### 4. Event log
Registra cada recordatorio, advertencia o cancelación automática.

## Conversaciones alcanzadas

La revisión periódica debe considerar solo conversaciones que:

- estén activas
- no estén finalizadas
- tengan un paso actual que espere acción del usuario
- no hayan sido ya canceladas
- no hayan sido ya notificadas en el umbral correspondiente

## Umbrales

El sistema debe soportar al menos dos umbrales configurables.

## 1. Primer umbral de inactividad

Cuando se supera este umbral, el sistema debe enviar un recordatorio automático.

### Ejemplos de comportamiento
- recordar al usuario que el proceso sigue pendiente
- invitar a continuar
- advertir que si no responde el flujo puede cancelarse

### Datos a registrar
- fecha y hora
- conversación
- teléfono
- paso actual
- tipo de evento: `timeout_warning_1`

## 2. Segundo umbral de inactividad

Si luego del recordatorio el usuario sigue sin responder y se supera un segundo período configurable, el sistema debe:

- advertir cancelación inminente
- o cancelar directamente
- según la regla que se adopte en la implementación

### Política implementada en esta etapa
En el MVP actual, el segundo umbral cancela directamente la conversación por inactividad y envía el mensaje final de cancelación.

La acción del segundo umbral queda explicitada además en configuración mediante:

- `medicina_laboral.conversation.second_inactivity_action`

Valor soportado actualmente:

- `cancel`

### Datos a registrar
- fecha y hora
- conversación
- teléfono
- paso actual
- tipo de evento: `timeout_warning_2` o `conversation_cancelled`

## 3. Cancelación automática

Si se cumplen las condiciones configuradas, la conversación debe cerrarse automáticamente por inactividad.

### Regla acordada
Cancelar no significa borrar.

Cancelar significa:
- marcar la conversación como finalizada
- dejarla inactiva
- registrar motivo de finalización
- conservar mensajes y eventos
- impedir reutilización de esa conversación para un flujo nuevo

## Parámetros configurables

Ningún umbral debe quedar hardcodeado.

## Ejemplos de parámetros

- minutos hasta primer recordatorio
- minutos hasta segundo umbral
- política de cancelación automática
- mensajes a enviar en cada umbral
- horarios o ventanas si se quiere restringir ejecución futura

## Ubicación sugerida

- `config/medicina_laboral.php`

En el estado actual del repo, el scheduler corre:

- `conversations:process-timeouts`
- cada minuto
- con `withoutOverlapping(10)` para evitar solapamientos accidentales

## Campos sugeridos en conversación

Para soportar esta lógica, la conversación debería poder registrar al menos:

- `ultimo_mensaje_recibido_en`
- `primer_umbral_notificado_en`
- `segundo_umbral_notificado_en`
- `expira_en`
- `finalizada_en`
- `motivo_finalizacion`
- `activa`

## Criterio de tiempo base

La referencia temporal principal para medir inactividad debería ser:

- el último instante en que se recibió un mensaje válido o procesable del usuario

Dependiendo del diseño final, también podría considerarse:

- último mensaje entrante total
- última transición efectiva del flujo

La regla debe quedar documentada y ser consistente.

## Registro técnico del evento

La documentación funcional exige registrar internamente el evento de cancelación por inactividad.

## Como mínimo registrar

- fecha y hora
- teléfono desde el cual se envió el mensaje
- tipo de evento
- etapa del flujo
- conversación asociada
- información técnica complementaria

## Recomendación de implementación

Registrar estos hechos en una tabla de eventos de conversación, por ejemplo:

- `conversacion_eventos`

Tipos sugeridos:
- `timeout_warning_1`
- `timeout_warning_2`
- `conversation_cancelled_by_inactivity`

## Asociación con la conversación

Toda la conversación ya debe tener asociados sus mensajes y eventos.  
Por lo tanto, la cancelación por inactividad no requiere ninguna reasignación especial.

Simplemente se debe:

- registrar un evento sobre esa conversación
- actualizar su estado
- marcar el motivo de finalización

Esto garantiza que el historial completo quede naturalmente vinculado a la cancelación.

## Qué pasa si el usuario vuelve a escribir después

Si el usuario vuelve a escribir una vez cancelada la conversación por inactividad:

- no debe reabrirse automáticamente la conversación cerrada
- debe iniciarse una nueva conversación
- los mensajes posteriores deben pertenecer a la nueva conversación

Esto evita mezclar intentos distintos.

## Scheduler en Laravel

## Ejecución sugerida

Registrar una tarea periódica en Laravel Scheduler para revisar conversaciones activas.

## Modalidades posibles

### Opción A
Revisión cada minuto

Ventaja:
- mayor precisión temporal

### Opción B
Revisión cada pocos minutos

Ventaja:
- menor carga operativa

La frecuencia concreta dependerá del tamaño del sistema y la precisión deseada.

## Estructura sugerida

### Comando
Un comando como:
- revisar conversaciones activas
- detectar umbrales superados
- disparar envío de recordatorios
- cancelar cuando corresponda

### Servicio
Un servicio específico puede encapsular:
- cálculo de vencimientos
- evaluación de estados
- ejecución de acciones
- registro de eventos

### Jobs opcionales
Si se desea desacoplar envíos de mensajes:
- job para recordatorio
- job para aviso de cancelación
- job para cierre técnico

## Mensajes automáticos

Los mensajes enviados por inactividad no deben estar hardcodeados.

## Ubicación sugerida

- `lang/es/whatsapp.php`
- Blade si se requiere mayor complejidad

## Ejemplos de mensajes

- recordatorio de continuidad del trámite
- advertencia de cancelación próxima
- confirmación de cancelación por inactividad

## Consideraciones operativas

## Idempotencia
La tarea periódica debe evitar enviar varias veces el mismo recordatorio.

Por eso es importante guardar marcas como:
- `primer_umbral_notificado_en`
- `segundo_umbral_notificado_en`

## Estados elegibles
No toda conversación activa necesariamente debe recibir recordatorios.

Ejemplos de conversaciones que podrían excluirse:
- ya finalizadas
- ya canceladas
- en estado técnico transitorio
- esperando una acción interna y no del usuario

## Trazabilidad
Cada acción automática debe dejar:
- mensaje enviado
- evento registrado
- timestamps consistentes

## Métricas sugeridas

- cantidad de recordatorios enviados
- cantidad de cancelaciones por inactividad
- pasos donde más se produce abandono
- tiempo promedio hasta abandono
- porcentaje de conversaciones recuperadas tras recordatorio

## Criterio de aceptación

La estrategia de inactividad se considerará correctamente implementada cuando el sistema pueda:

- detectar conversaciones inactivas
- enviar recordatorios una única vez por umbral
- registrar eventos técnicos
- cancelar automáticamente sin borrar historial
- impedir reutilización de conversaciones canceladas
- iniciar una nueva conversación si el usuario vuelve a escribir
