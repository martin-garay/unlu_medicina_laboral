# Plan de implementación y desarrollo

## Objetivo

Este documento organiza el plan de implementación del proyecto Medicina Laboral WhatsApp MVP en etapas concretas, atómicas y orientadas al desarrollo.

La idea es usarlo como base de trabajo para ir sacando tareas, pedir prompts puntuales y mantener una secuencia ordenada de implementación.

---

## Rol de este documento

Este archivo es el **plan maestro** del proyecto.

Debe funcionar como fuente principal para:

- contexto estable
- etapas
- milestones
- roadmap general
- orden recomendado de implementación

Cuando se necesiten planes de trabajo más cortos o de una tanda específica, esos documentos deben vivir como anexos operativos o históricos, no como otra fuente principal en competencia con este plan.

---

## Principios del plan

- avanzar de lo estructural a lo funcional
- evitar hardcodear lógica, mensajes y parámetros
- separar conversación de entidades de negocio
- dejar trazabilidad desde el comienzo
- priorizar mantenibilidad y extensibilidad
- construir primero una base reusable antes de cerrar los flujos completos
- mantener documentación visual viva en formatos texto versionables

---

## Etapa 1: base documental y decisiones

### Objetivo
Dejar documentado el contexto funcional, alcance, arquitectura, modelo de datos y decisiones técnicas del proyecto.

### Alcance
Crear y mantener:

- `README.md`
- `AGENTS.md`
- `docs/`
- `docs/diagrams/`
- `PLANS.md`

### Entregables
- documentación base del proyecto
- reglas de diseño claras
- contexto suficiente para devs y agentes de IA
- convención explícita de diagramas como código para flujos, clases y base de datos

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

## Etapa 8.5: base de testing y cobertura inicial

### Objetivo
Incorporar formalmente la estrategia de testing una vez completada la base funcional principal de los flujos conversacionales.

### Por qué aparece en este punto
Hasta la etapa 8 el foco está en estabilizar la arquitectura, los contratos y los recorridos funcionales principales.

Desde este punto:

- ya existe una base conversacional testeable
- hay piezas reutilizables y relativamente estables
- seguir agregando lógica sin tests empieza a aumentar el riesgo de regresión

### Alcance
Formalizar la política de testing del proyecto y construir la base mínima para ejecutar tests de forma repetible.

### Qué se debe testear primero
- `StepResult`
- validadores por paso
- `ConversationFlowResolver`
- handlers pequeños o con branching relevante
- servicios base de conversación
- servicios de materialización de negocio ya existentes
- casos críticos del flujo de aviso y del flujo de anticipo en la capa hoy más estable

### Qué no cubrir todavía
- cobertura exhaustiva de todos los controllers
- pruebas end-to-end complejas contra proveedores externos
- infraestructura completa de CI/CD si el repo todavía no la necesita
- automatizaciones de browser o integración externa real

### Política obligatoria a partir de esta etapa
Desde esta etapa en adelante, todo cambio relevante de implementación debe incluir sus tests dentro del mismo commit cuando corresponda.

Esto aplica especialmente a:

- lógica nueva con branching no trivial
- validadores nuevos
- handlers nuevos
- servicios con reglas de negocio
- bugfixes que corrigen comportamiento observable

La ausencia de tests solo es aceptable si queda justificada explícitamente en el cambio.

### Criterio de aceptación
La etapa se considera cumplida cuando:

- existe infraestructura mínima para ejecutar tests localmente
- el enfoque de testing está documentado
- el plan maestro deja explícito que los pasos siguientes deben incluir tests en el mismo commit
- el proyecto tiene una base inicial para empezar a agregar cobertura incremental

---

## Desglose operativo recomendado para la implementación conversacional

Además de las etapas y milestones, conviene ejecutar la base conversacional en incrementos cortos y revisables.

### Bloque operativo A
- centralizar textos, mensajes institucionales y parámetros
- crear `lang/es/whatsapp.php`
- crear `config/medicina_laboral.php`
- crear templates Blade base

### Bloque operativo B
- definir la estructura extensible de flujo
- crear `ConversationFlowResolver`
- crear contratos `StepHandler` y `Validator`
- crear `StepResult`, `ValidationResult` y `MessageResolver`

### Bloque operativo C
- hacer un refactor mínimo del webhook actual
- mover ramas concretas del controller a handlers
- lograr que `StepResult` gobierne más decisiones reales

### Bloque operativo D
- implementar menú principal conversacional real
- selección entre aviso y anticipo
- cancelar y volver al menú principal

### Bloque operativo E
- implementar identificación común reutilizable
- pedir nombre, legajo, sede y jornada laboral
- persistir borrador en `metadata.identificacion`

### Bloque operativo F
- implementar tramo inicial del aviso
- fecha desde
- fecha hasta
- tipo de ausentismo
- motivo
- domicilio circunstancial
- observaciones
- persistir borrador en `metadata.aviso`

### Bloque operativo G
- implementar confirmación final del aviso
- crear `Aviso` real
- asociar conversación y aviso
- cerrar conversación de forma consistente

### Bloque operativo H
- implementar tramo inicial del anticipo de certificado
- número de aviso
- validación de aviso elegible
- tipo de certificado
- paso de adjunto
- persistir borrador en `metadata.certificado`

### Bloque operativo I
- implementar confirmación final del anticipo
- crear `AnticipoCertificado`
- persistir archivos asociados
- cerrar conversación de forma consistente

### Bloque operativo J
- incorporar infraestructura mínima de testing
- documentar criterios de cobertura y aceptación
- comenzar por validadores, handlers y servicios estables
- hacer obligatorio que los pasos posteriores incluyan tests en el mismo commit

### Bloque operativo K
- mantener diagramas de flujo en Mermaid
- mantener diagramas de clases en PlantUML
- mantener esquema de base de datos en DBML
- actualizar `docs/diagrams/` cuando cambian flujos o estructura relevante
- usar los diagramas como soporte de contexto para prompts futuros a Codex

### Criterio de uso

Estos bloques operativos sirven para pedir prompts más concretos sin perder alineación con las etapas maestras.

Mapeo sugerido:

- bloques A-B -> Etapas 3-4
- bloques C-D -> Etapas 4-5
- bloque E -> Etapa 6
- bloques F-G -> Etapa 7
- bloques H-I -> Etapa 8
- bloque J -> Etapa 8.5

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

### Regla de ejecución desde esta etapa
Las mejoras o cambios relevantes dentro de esta etapa deben venir acompañados por tests en el mismo commit, salvo excepción justificada.

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

### Regla de ejecución desde esta etapa
Los cambios de automatismos o reglas temporales deben incluir tests apropiados en el mismo commit, especialmente cuando afecten cancelación, recordatorios o cierres automáticos.

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

### Regla de ejecución desde esta etapa
Si una modificación de templates o mensajes cambia comportamiento de flujo o criterios de decisión, debe incorporar tests o ajustar los existentes en el mismo commit cuando corresponda.

---

## Etapa 12: integraciones futuras y endurecimiento

### Objetivo
Preparar la transición desde MVP a una solución más integrada.

### Alcance futuro
- integración real con Mapuche para validación e identificación del trabajador
- identificación real del trabajador
- envío de emails
- storage definitivo de archivos
- catálogos en base de datos
- adapter formal para payloads externos
- integraciones con sistemas externos
- mayor desacople entre proveedor externo y dominio interno

### Roadmap específico de Mapuche
Antes de esta etapa, el proyecto puede apoyarse en una abstracción chica como `MapucheWorkerProvider` con implementación mock para desarrollo.

En esta etapa deberá abordarse:
- reemplazo o complemento del mock por una implementación real contra Mapuche o API Mapuche
- validación real de legajo
- obtención de nombre, sede y jornada laboral desde el sistema externo
- manejo explícito de errores de integración
- mantenimiento del desacople entre flujos conversacionales y proveedor externo

### Estado esperado
El sistema puede evolucionar sin necesidad de reescribir la base conversacional.

### Regla de ejecución desde esta etapa
Toda integración o endurecimiento relevante debe llegar con cobertura adecuada dentro del mismo commit, priorizando tests unitarios e integraciones livianas sobre verificaciones manuales aisladas.

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
- derivar bloques operativos concretos sin duplicar el rol de plan maestro

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
