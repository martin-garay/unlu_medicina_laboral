
---

## `docs/12-decisiones-tecnicas.md`

```md
# Decisiones técnicas

## Objetivo

Este documento registra las decisiones técnicas principales ya tomadas para el proyecto, con el fin de:

- alinear a desarrolladores y agentes de IA
- evitar rediscutir definiciones ya acordadas
- dejar trazabilidad de criterios de diseño
- facilitar mantenimiento y evolución del sistema

## Estado del documento

Este documento es incremental.  
Debe actualizarse a medida que se tomen nuevas decisiones relevantes.

---

## 1. Canal principal del MVP

### Decisión
El MVP se implementa sobre **WhatsApp Cloud API** como canal conversacional principal.

### Motivo
- está alineado con el alcance funcional actual
- evita una app móvil propia en esta etapa
- reduce complejidad de adopción para el usuario final
- permite avanzar sobre el flujo real del negocio

---

## 2. Stack principal

### Decisión
El sistema base se implementa con:

- Laravel
- PHP
- PostgreSQL
- Docker / Docker Compose

### Motivo
- ya existe una base inicial construida con este stack
- permite iterar rápido
- facilita integración con scheduler, colas y estructura MVC/servicios

---

## 3. Separación entre conversación y aviso

### Decisión
La **conversación** y el **aviso** son entidades distintas.

### Motivo
Una conversación representa una sesión técnica y trazable de interacción.  
Un aviso representa un registro de negocio.

Esto permite:
- cancelar flujos sin ensuciar registros de negocio
- registrar errores e intentos
- soportar inactividad
- asociar solo el flujo exitoso al aviso creado

---

## 4. Separación entre conversación y anticipo de certificado

### Decisión
La **conversación** y el **anticipo de certificado** también son entidades distintas.

### Motivo
El anticipo debe materializarse solo al final del flujo y debe quedar asociado a un aviso previo.

---

## 5. No borrar conversaciones ni mensajes

### Decisión
No se deben borrar físicamente conversaciones ni mensajes por cancelación, error o inactividad.

### Motivo
Se requiere trazabilidad técnica y funcional completa.

### Implicancia
Cancelar significa:
- cerrar la conversación
- marcarla como inactiva/finalizada
- conservar mensajes y eventos
- impedir reutilización para nuevos flujos

---

## 6. Nueva conversación después de cancelar

### Decisión
Si el usuario vuelve a interactuar luego de una cancelación o expiración, debe iniciarse una **nueva conversación**.

### Motivo
Evita mezclar mensajes de distintos intentos bajo una misma sesión lógica.

---

## 7. Toda interacción asociada a conversación

### Decisión
Todos los mensajes entrantes y salientes deben quedar asociados a una conversación.

### Motivo
Esto permite:
- reconstrucción del flujo
- métricas
- auditoría
- asociación correcta con aviso o anticipo final

---

## 8. Registrar mensajes válidos e inválidos

### Decisión
Cada mensaje relevante debe permitir marcarse como:

- válido
- inválido

y registrar motivo si corresponde.

### Motivo
Se quiere medir fricción del flujo, errores por paso y cantidad de intentos hasta concretar un aviso o anticipo.

---

## 9. Laravel Scheduler para automatismos

### Decisión
Los automatismos de inactividad deben implementarse con **Laravel Scheduler**.

### Motivo
- encaja naturalmente con el framework
- centraliza lógica temporal
- desacopla esta responsabilidad del webhook
- permite recordatorios y cancelación automática

---

## 10. Textos fuera del código

### Decisión
No deben quedar textos hardcodeados en controllers o services.

### Implementación elegida
- mensajes cortos y estructurados en `lang/es/*.php`
- mensajes largos o plantillas en Blade

### Motivo
- mantenibilidad
- reutilización
- futura administración dinámica
- mejor separación entre lógica y contenido

---

## 11. Parámetros fuera del código

### Decisión
Los parámetros configurables deben moverse a archivos de configuración.

### Ubicación sugerida
- `config/medicina_laboral.php`

### Ejemplos
- cantidad de intentos
- umbrales de inactividad
- formatos permitidos
- tamaño máximo de archivos
- plazo para anticipo de certificado

---

## 12. Validaciones extensibles por paso

### Decisión
Las validaciones deben diseñarse por paso de flujo, evitando un controller monolítico o un `switch` gigante.

### Motivo
- escalabilidad
- mantenibilidad
- reutilización
- incorporación simple de nuevas reglas

### Enfoque recomendado
Separar:
- handler de paso
- validator
- message resolver
- resultado de transición

---

## 13. Integraciones externas desacopladas

### Decisión
Las integraciones externas, como identificación real del trabajador, deben quedar detrás de interfaces o servicios desacoplados.

### Motivo
Permite:
- usar mocks mientras no exista integración real
- probar el flujo sin bloquearse
- cambiar implementación futura con menor impacto

---

## 14. Identificación del trabajador mockeada en primera etapa

### Decisión
La identificación real del trabajador se deja temporalmente mockeada o encapsulada detrás de un servicio reemplazable.

### Motivo
El foco inicial está en el motor de conversación y en los flujos.

### Implementación base
- contrato de aplicación `WorkerIdentificationService` para el flujo conversacional
- implementación `MockWorkerIdentificationService` como driver por defecto del flujo mientras no exista integración real
- adaptador `MapucheWorkerIdentificationService` para reutilizar el proveedor de integración disponible
- contrato pequeño `MapucheWorkerProvider` para lookup por legajo
- implementación `MockMapucheWorkerProvider` configurable para desarrollo
- almacenamiento del resultado del lookup dentro de `metadata.identificacion.worker_lookup` cuando aplique

### Evolución prevista
La implementación real contra Mapuche queda diferida y deberá reemplazar o complementar el mock sin acoplar los handlers conversacionales al proveedor externo.

### Datos mínimos esperados de la integración futura
- legajo
- nombre completo
- sede
- jornada laboral

### Endurecimiento base recomendado
- `BusinessNotificationSender` con implementación `null` para preparar envío real de emails sin acoplar servicios de negocio
- `DraftAttachmentStorage` para encapsular la captura de metadata de adjuntos antes del storage definitivo
- selección de drivers centralizada en `config/medicina_laboral.php`

---

## 15. Catálogo de tipos de certificado

### Decisión provisoria
En una primera etapa, el catálogo de tipos de certificado puede resolverse con:

- config
- enum

y no necesariamente con una tabla de base de datos.

### Motivo
Evitar complejidad prematura.

---

## 16. Persistencia transitoria de identificación en conversación

### Decisión
Los datos de identificación comunes del trabajador se guardan transitoriamente en `metadata.identificacion` dentro de `conversaciones`.

### Motivo
- evita una tabla adicional prematura
- mantiene el borrador asociado a la sesión técnica
- permite reutilizar la identificación en aviso y certificado
- facilita cambiar después la estrategia de persistencia si hiciera falta

### Pendiente
Reevaluar si el catálogo debe administrarse desde backoffice.

---

## 17. Confirmaciones largas con Blade

### Decisión
Los mensajes largos o con estructura variable deben resolverse con templates Blade.

### Motivo
- evita hardcodear textos institucionales extensos
- mejora mantenibilidad
- permite reutilización y parametrización

---

## 18. Testing obligatorio a partir de la base funcional principal

### Decisión
Una vez completada la base funcional principal de los flujos conversacionales, los cambios relevantes deben incluir tests dentro del mismo commit cuando corresponda.

### Motivo
- reducir regresiones
- aprovechar que la arquitectura ya separa handlers, validadores y servicios
- evitar seguir acumulando lógica sin cobertura mínima razonable

### Alcance inicial sugerido
- validadores
- `StepResult`
- resolvedores de flujo
- handlers con branching relevante
- servicios de conversación
- servicios de materialización de negocio

## 17. Persistencia transitoria de anticipo en conversación

### Decisión
Los datos intermedios del flujo de anticipo de certificado se guardan temporalmente en `metadata.certificado` dentro de `conversaciones`.

### Motivo
- evita crear tablas intermedias prematuras
- mantiene el borrador técnico asociado a la conversación
- permite validar y completar el flujo antes de materializar la entidad de negocio final
- facilita registrar metadata mínima de adjuntos sin definir todavía el storage definitivo

### Decisión
Los mensajes de resumen final y confirmación deben construirse con templates Blade.

### Motivo
- permiten interpolar variables fácilmente
- ordenan mejor el contenido
- dejan base para futura edición desde admin

---

## 17. El anticipo requiere aviso previo

### Decisión
No se debe permitir registrar un anticipo de certificado sin aviso previo elegible.

### Motivo
Es una regla funcional central del dominio.

---

## 18. Trazabilidad como requerimiento central

### Decisión
La trazabilidad no es un extra: es parte esencial del diseño.

### Implicancias
Se debe poder saber:
- qué conversación generó un aviso
- qué conversación generó un anticipo
- cuántos mensajes se necesitaron
- qué errores hubo
- en qué paso se canceló o expiró

---

## 19. Documentación para desarrolladores en `docs/`

### Decisión
La documentación técnica y funcional detallada para programadores debe vivir en `docs/`.

### Motivo
Centralizar conocimiento del proyecto y facilitar onboarding.

---

## 20. Guía operativa para agentes en `AGENTS.md`

### Decisión
El repositorio debe incluir un `AGENTS.md` en la raíz con reglas breves y operativas para agentes de IA.

### Motivo
Mejorar consistencia del trabajo automatizado sobre el repo y asegurar lectura mínima de contexto.

---

## Pendientes técnicos relevantes

Estos temas están identificados pero no necesariamente resueltos aún:

- diseño concreto de tablas `conversaciones`, `conversacion_mensajes` y `conversacion_eventos`
- definición exacta de estados del flujo
- definición exacta de catálogos iniciales
- implementación concreta de handlers y validadores por paso
- política exacta de timeout por umbrales
- storage final de archivos adjuntos
- envío real de emails
- estrategia de asociación de mensajes al aviso efectivo
- posible uso futuro de Adapter para desacoplar payloads externos

---

## Próximas decisiones a formalizar

A futuro conviene agregar decisiones sobre:

- modelo de datos final
- estrategia de colas/jobs
- manejo de archivos
- integración real con sistemas externos
- criterios de versionado de flujos
- administración de catálogos desde panel

---

## 21. Diagramas como código

### Decisión
La documentación visual del proyecto se mantiene como texto versionable dentro de `docs/diagrams/`.

### Formatos elegidos
- Mermaid para flujos conversacionales
- PlantUML para diagramas de clases
- DBML para esquema de base de datos

### Motivo
- diff legible en Git
- mantenimiento simple
- soporte natural para PRs
- buena compatibilidad con prompts y agentes como Codex
- documentación viva alineada con el código

### Implicancia
Cuando cambien flujos relevantes, relaciones estructurales o esquema de datos, los diagramas afectados deben actualizarse junto con la documentación correspondiente.

---

## 22. Evolución prevista hacia múltiples canales

### Decisión
Aunque el MVP actual usa WhatsApp Cloud API como canal principal, el motor conversacional debe poder evolucionar para soportar otros canales internos sin reescribir handlers, validadores ni servicios de negocio.

### Implicancia de diseño
La evolución esperada es:
- mantener adapters de entrada por canal
- normalizar el mensaje entrante a un formato interno estable
- resolver la salida mediante un sender por canal o una abstracción equivalente
- conservar `conversaciones`, `conversacion_mensajes` y `conversacion_eventos` como trazabilidad común

### Aplicación futura
Esto habilita, por ejemplo:
- consola web interna para desarrollo
- interfaz administrativa de simulación de conversaciones
- canal interno alternativo reutilizable más allá de WhatsApp

### Nota
Hoy la frontera de entrada/salida sigue concentrada en el webhook y en `WhatsAppSender`, por lo que esta evolución requiere un desacople moderado en esa capa, no una reescritura del motor conversacional.
