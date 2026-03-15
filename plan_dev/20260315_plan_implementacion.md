# Plan de implementación y desarrollo

## Objetivo

Este documento organiza el plan de implementación del proyecto Medicina Laboral WhatsApp MVP en etapas concretas, atómicas y orientadas al desarrollo.

La idea es usarlo como base de trabajo para ir sacando tareas, pedir prompts puntuales y mantener una secuencia ordenada de implementación.

---

## Principios del plan

- avanzar de lo estructural a lo funcional
- evitar hardcodear lógica, mensajes y parámetros
- separar conversación de entidades de negocio
- dejar trazabilidad desde el comienzo
- priorizar mantenibilidad y extensibilidad
- construir primero una base reusable antes de cerrar los flujos completos

---

## Etapa 1: base documental y decisiones

### Objetivo
Dejar documentado el contexto funcional, alcance, arquitectura, modelo de datos y decisiones técnicas del proyecto.

### Alcance
Crear y mantener:

- `README.md`
- `AGENTS.md`
- `docs/`
- `PLANS.md`

### Entregables
- documentación base del proyecto
- reglas de diseño claras
- contexto suficiente para devs y agentes de IA

### Estado esperado
El repo debe quedar entendible para una persona nueva sin depender de contexto oral.

---

## Etapa 2: base del motor de conversación

### Objetivo
Implementar la estructura mínima para sostener sesiones conversacionales trazables.

### Alcance
Crear la base persistente y lógica mínima para:

- iniciar conversación
- recuperar conversación activa
- registrar mensajes
- registrar eventos
- contar intentos
- cerrar conversación

### Componentes
- tabla `conversaciones`
- tabla `conversacion_mensajes`
- tabla `conversacion_eventos`

### Servicios sugeridos
- `ConversationManager`
- `ConversationMessageService`
- `ConversationEventService`

### Entregables
- migraciones
- modelos
- relaciones Eloquent
- servicios base de conversación

### Estado esperado
El sistema puede asociar toda interacción a una conversación y dejar trazabilidad, aunque todavía no exista el flujo completo.

---

## Etapa 3: centralización de textos y parámetros

### Objetivo
Evitar que la app crezca con textos y valores hardcodeados.

### Alcance
Mover mensajes y parámetros a ubicaciones centralizadas.

### Componentes
- `lang/es/whatsapp.php`
- `config/medicina_laboral.php`
- templates Blade para mensajes largos

### Tipos de mensajes
- menú principal
- mensajes de solicitud de datos
- errores de validación
- confirmaciones
- cancelaciones
- recordatorios de inactividad
- mensajes finales

### Entregables
- archivo de idioma inicial
- archivo de configuración inicial
- primeros templates Blade

### Estado esperado
Los cambios de textos o parámetros no requieren tocar lógica de negocio.

---

## Etapa 4: estructura del flujo por pasos

### Objetivo
Definir una arquitectura extensible para procesar los flujos sin caer en un controller monolítico.

### Alcance
Diseñar la base para manejar pasos, validaciones y transiciones.

### Componentes sugeridos
- `ConversationFlowResolver`
- `StepHandler`
- `Validator`
- `MessageResolver`
- `StepResult`

### Objetivo de diseño
Cada paso del flujo debe poder definir:

- dato esperado
- validación
- mensaje de error
- siguiente paso
- cantidad máxima de intentos
- evento asociado

### Entregables
- contratos base
- estructura inicial de carpetas
- primer flujo simple usando esta arquitectura

### Estado esperado
Se pueden agregar pasos sin romper la mantenibilidad del sistema.

---

## Etapa 5: menú principal y navegación base

### Objetivo
Implementar el punto de entrada conversacional del sistema.

### Alcance
Permitir al usuario iniciar al menos estos flujos:

- aviso de ausencia
- anticipo de certificado médico

Y además:
- cancelar y volver al menú
- reiniciar flujo correctamente
- no reutilizar conversaciones canceladas

### Entregables
- menú principal implementado
- selección de flujo
- navegación básica entre estados iniciales

### Estado esperado
El sistema ya puede conducir al usuario hacia el flujo correcto.

---

## Etapa 6: flujo de identificación común

### Objetivo
Implementar los pasos compartidos de identificación del trabajador.

### Datos a solicitar
- nombre completo
- legajo
- sede
- jornada laboral

### Alcance
Construir estos pasos como parte reusable para ambos flujos principales.

### Consideración técnica
La identificación real puede quedar desacoplada mediante:

- interfaz
- servicio mock temporal

### Entregables
- handlers de identificación
- validaciones base
- persistencia de datos transitorios en conversación

### Estado esperado
Ambos flujos pueden apoyarse en una base común de identificación.

---

## Etapa 7: flujo de aviso de ausencia

### Objetivo
Implementar el flujo completo para registrar un aviso de ausencia.

### Pasos
- identificación
- fecha desde
- fecha hasta
- tipo de ausentismo
- motivo
- domicilio circunstancial
- observaciones
- datos de familiar cuando corresponda
- confirmación final
- registración efectiva

### Entidad de negocio
- `Aviso`

### Entregables
- flujo completo de aviso
- validaciones mínimas
- resumen final
- alta de aviso
- asociación entre conversación y aviso

### Estado esperado
El usuario puede completar un aviso y el sistema lo registra como entidad de negocio separada.

---

## Etapa 8: flujo de anticipo de certificado

### Objetivo
Implementar el flujo completo de anticipo de certificado médico.

### Pasos
- identificación
- identificación de aviso previo
- validación de aviso elegible
- tipo de certificado
- adjuntar archivo
- confirmación final
- registración efectiva

### Entidades de negocio
- `AnticipoCertificado`
- `AnticipoCertificadoArchivo`

### Reglas clave
- requiere aviso previo
- debe validar elegibilidad del aviso
- debe validar adjuntos

### Entregables
- flujo completo de anticipo
- asociación a aviso
- soporte básico de adjuntos
- alta de anticipo y archivos asociados

### Estado esperado
El sistema puede registrar anticipos correctamente vinculados a un aviso.

---

## Etapa 9: validaciones, intentos y errores

### Objetivo
Endurecer los flujos con reglas por paso y trazabilidad de errores.

### Alcance
Agregar soporte consistente para:

- mensajes válidos
- mensajes inválidos
- códigos de error
- intentos por paso
- intentos totales
- superación de umbrales
- cancelación o derivación según regla

### Ejemplos
- legajo inválido
- fecha inválida
- opción inexistente
- aviso no elegible
- archivo no permitido
- tamaño excedido

### Entregables
- validadores por paso
- códigos de error estables
- incremento de intentos
- eventos de validación fallida

### Estado esperado
El sistema puede manejar errores sin perder claridad ni mantenibilidad.

---

## Etapa 10: inactividad y automatismos

### Objetivo
Implementar recordatorios y cancelaciones automáticas por inactividad.

### Alcance
Usar Laravel Scheduler para:

- detectar conversaciones inactivas
- enviar recordatorio automático
- aplicar segundo umbral
- cancelar conversación por inactividad
- registrar eventos automáticos

### Componentes sugeridos
- `ConversationTimeoutService`
- comando o job scheduler
- eventos de timeout

### Entregables
- tarea programada
- mensajes automáticos
- registro de eventos de inactividad
- cierre correcto de conversación

### Estado esperado
La conversación se puede abandonar sin dejar el sistema en un estado inconsistente.

---

## Etapa 11: mensajes finales y templates

### Objetivo
Completar la experiencia conversacional con mensajes bien estructurados y reutilizables.

### Alcance
Crear templates Blade para:

- confirmación final de aviso
- aviso registrado
- confirmación final de anticipo
- anticipo registrado
- cancelación
- recordatorios por inactividad

### Entregables
- templates Blade
- uso integrado desde handlers o servicios
- mensajes trazables por key o template

### Estado esperado
Los mensajes largos están desacoplados de la lógica y listos para futura administración.

---

## Etapa 12: integraciones futuras y endurecimiento

### Objetivo
Preparar la transición desde MVP a una solución más integrada.

### Alcance futuro
- identificación real del trabajador
- envío de emails
- storage definitivo de archivos
- catálogos en base de datos
- adapter formal para payloads externos
- integraciones con sistemas externos
- mayor desacople entre proveedor externo y dominio interno

### Estado esperado
El sistema puede evolucionar sin necesidad de reescribir la base conversacional.

---

## Resumen por milestones

## Milestone 1
- documentación
- conversaciones
- mensajes
- eventos

## Milestone 2
- textos
- parámetros
- templates base
- estructura por pasos

## Milestone 3
- menú principal
- identificación común

## Milestone 4
- flujo completo de aviso de ausencia

## Milestone 5
- flujo completo de anticipo de certificado

## Milestone 6
- validaciones avanzadas
- intentos
- trazabilidad de errores

## Milestone 7
- scheduler
- inactividad
- cancelación automática

## Milestone 8
- integraciones reales y refactors de endurecimiento

---

## Primer bloque recomendado para empezar a codear

Si se quiere arrancar ya con implementación ordenada, el primer bloque concreto es:

1. migración `conversaciones`
2. migración `conversacion_mensajes`
3. migración `conversacion_eventos`
4. modelos Eloquent correspondientes
5. `ConversationManager`
6. `ConversationMessageService`
7. `ConversationEventService`
8. `lang/es/whatsapp.php`
9. `config/medicina_laboral.php`

---

## Forma sugerida de usar este plan

Este archivo puede usarse como base para:

- pedir prompts puntuales
- sacar tareas unitarias
- marcar progreso
- revisar orden de implementación
- alinear devs y agentes de IA

### Ejemplos de uso
- “Trabajemos la Etapa 2, punto 1”
- “Dame la migración para conversaciones”
- “Dame el prompt para implementar ConversationManager”
- “Armemos la Etapa 7 paso por paso”
- “Pasame checklist de la Etapa 10”

---

## Criterio general de éxito

El plan se considera bien ejecutado si el proyecto logra:

- una base conversacional trazable
- flujos mantenibles
- separación entre conversación y negocio
- soporte de errores, cancelación e inactividad
- documentación útil para devs y agentes
- capacidad de crecer sin refactor estructural grande
