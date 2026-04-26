
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
- `FinalAttachmentStorage` para encapsular la persistencia final de adjuntos asociados al anticipo
- selección de drivers centralizada en `config/medicina_laboral.php`

### Evolución mínima admitida para email
Sin salir todavía a una integración productiva compleja, puede existir un driver opcional basado en `Mail` de Laravel, manteniendo `null` como default y evitando que handlers o controllers dependan de esa implementación concreta.

---

## 15. Catálogo de tipos de certificado

### Decisión provisoria
En una primera etapa, el catálogo de tipos de certificado puede resolverse con:

- config
- enum

---

## 16. Base operativa preproductiva liviana

### Decisión
Sin implementar todavía despliegue productivo completo ni CI/CD compleja, el proyecto debe contar con una base mínima de operación repetible desde Docker.

### Implementación base
- `Makefile` con atajos operativos frecuentes
- comando `medicina:doctor` para chequeo rápido del entorno
- scheduler y timeouts ejecutables manualmente
- documentación corta de soporte en `docs/13-operacion-y-soporte.md`

### Motivo
- reducir fricción para desarrollo avanzado y QA
- facilitar diagnóstico de entorno
- dejar una rutina mínima antes de usar el sistema en entorno de prueba

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

### Plan técnico corto recomendado para una primera consola interna

La recomendación para una primera implementación no es empezar por la UI.

El orden sugerido es:

#### Milestone A
Extraer una capa de interacción común al canal.

Objetivo:
- encapsular la lógica que hoy vive repartida entre `WhatsappWebhookController`, registro de trazabilidad y aplicación de `StepResult`

Entregables mínimos:
- DTO interno de entrada
- DTO o estructura interna de salida
- servicio tipo `ConversationInteractionService`

Qué debe seguir igual:
- handlers
- validadores
- servicios de negocio
- persistencia de conversaciones, mensajes y eventos

#### Milestone B
Hacer que WhatsApp use esa nueva capa sin cambiar comportamiento funcional.

Objetivo:
- convertir el webhook actual en un adapter de canal

Entregables mínimos:
- `WhatsappWebhookController` reducido a parseo de request y delegación
- uso de sender/adapter de salida detrás de una abstracción
- `ConversationTimeoutService` desacoplado de `WhatsAppSender`

#### Milestone C
Exponer un canal interno mínimo orientado a texto.

Objetivo:
- habilitar pruebas funcionales del motor sin depender de WhatsApp

Entregables mínimos:
- endpoint/controlador interno para enviar mensajes al motor
- representación simple de salida en texto
- soporte inicial para opciones numeradas en vez de botones ricos

#### Milestone D
Agregar una UI mínima de consola local.

Objetivo:
- poder conversar manualmente con el motor desde el navegador o una interfaz interna simple

Entregables mínimos:
- vista básica de chat
- formulario de envío
- render de respuestas textuales
- render básico de opciones del menú principal

### Alcance recomendado de la primera versión

La primera versión debería incluir solamente:

- texto
- opciones numeradas o listas simples
- continuidad de conversación sobre la misma trazabilidad
- soporte suficiente para recorrer menú, identificación y flujos básicos

### Qué no conviene incluir en la primera versión

- frontend final o diseño definitivo
- websockets o tiempo real
- autenticación compleja
- multicanal real simultáneo
- paridad completa de adjuntos desde el primer corte
- reemplazo del canal WhatsApp existente

### Enfoque inicial recomendado

El punto de arranque recomendado es:

1. DTO interno + servicio de interacción
2. adapter de salida por canal
3. endpoint/controlador interno de prueba
4. UI mínima después

La razón es simple:

- reduce riesgo de refactor
- conserva el comportamiento vigente del canal principal
- permite validar el motor con un canal alternativo antes de diseñar interfaz
- deja una base reusable para otros canales futuros

---

## 23. Certificado como `AnticipoCertificado`

### Decisión
Cuando el proyecto habla de `certificado` en el alcance actual, se refiere a la entidad de negocio ya existente `AnticipoCertificado`.

No se crea una entidad nueva `certificados` en esta etapa.

### Motivo
El modelo actual ya materializa el anticipo de certificado y sus archivos asociados mediante:

- `anticipos_certificado`
- `anticipo_certificado_archivos`

Crear una entidad adicional duplicaría conceptos y aumentaría la complejidad sin una necesidad funcional nueva.

### Implicancia
La evolución para asociar un certificado a N avisos debe resolverse extendiendo la relación de `AnticipoCertificado` con `Aviso`, no introduciendo una tabla principal nueva de certificados.

---

## 24. Estado inicial de avisos y anticipos

### Decisión
El estado inicial funcional para avisos y anticipos/certificados será **Inicial**.

Como valor técnico recomendado en base de datos se usará `inicial`, manteniendo la etiqueta visible "Inicial".

### Motivo
Permite distinguir claramente registros recién creados por el flujo conversacional de estados posteriores con intervención de operador.

### Estados con intervención de operador previstos

Para avisos:

- `observado`
- `verificado`
- `cancelado`
- `invalido`

Para anticipos/certificados:

- `observado`
- `vinculado`
- `cancelado`
- `invalido`

### Implicancia
Las futuras migraciones deben agregar o ajustar el campo `estado` para que los registros nuevos nazcan en `inicial`, sin asumir todavía un workflow administrativo completo.

---

## 25. Permisos sin control por columnas o campos

### Decisión
El módulo administrativo no implementará permisos por columna o campo sensible en esta etapa.

### Alcance esperado
La autorización deberá modelarse inicialmente a nivel de:

- módulo
- acción
- entidad o recurso

### Motivo
Los permisos por campo agregan complejidad significativa al backend, frontend, auditoría y tests. Para el alcance actual se prioriza un modelo administrable y mantenible.

### Implicancia
Los roles mínimos (`admin`, `auditor`, `director`) deberán definirse con permisos de módulo/acción/recurso. La protección específica de campos sensibles queda fuera de alcance salvo decisión futura explícita.

---

## 26. Datos sensibles en logs

### Decisión
La política de datos sensibles en logs queda identificada como pendiente importante, pero no se implementa en el corte actual.

### Motivo
El sistema ya registra logs útiles para debugging y operación inicial, pero antes de ampliar el módulo administrativo conviene definir una política explícita de redacción, minimización y retención de datos sensibles.

### Implicancia
Este pendiente debe figurar en `plan_dev/BACKLOG.md` con prioridad alta y ser considerado antes de exponer información sensible en interfaces administrativas.

---

## 27. Storage real de adjuntos de `AnticipoCertificado`

### Decisión
La evolución de certificados debe usar la entidad actual `AnticipoCertificado` y sus archivos asociados en `anticipo_certificado_archivos`.

No se crea una entidad nueva `certificados`.

El primer storage binario real recomendado es un driver local sobre Laravel Filesystem, manteniendo los drivers metadata actuales como fallback/desarrollo.

### Estado actual
El sistema ya tiene los contratos:

- `DraftAttachmentStorage`
- `FinalAttachmentStorage`

Y las implementaciones metadata-only:

- `MetadataDraftAttachmentStorage`
- `MetadataFinalAttachmentStorage`

Hoy el flujo conversacional guarda metadata del adjunto en `metadata.certificado.adjuntos` y al confirmar materializa registros en `anticipo_certificado_archivos`.

### Driver local propuesto
Agregar implementaciones:

- `LocalDraftAttachmentStorage`
- `LocalFinalAttachmentStorage`

Responsabilidades esperadas:

- descargar el binario desde WhatsApp usando `provider_media_id`
- validar MIME permitido desde `medicina_laboral.certificados.allowed_mime_types`
- validar tamaño máximo desde `medicina_laboral.certificados.max_size_kb`
- calcular hash del archivo almacenado
- guardar en `storage/app/...` usando directorios ya parametrizados:
  - `medicina_laboral.storage.draft_attachments.directory`
  - `medicina_laboral.storage.final_attachments.directory`
- devolver `StoredDraftAttachment` / `StoredFinalAttachment` con `storage_status = stored`

### Cliente de media de WhatsApp
La descarga no debe vivir en handlers ni controllers.

Se recomienda agregar un servicio chico, por ejemplo:

- contrato `WhatsAppMediaDownloader`
- implementación HTTP para WhatsApp Cloud API
- fake para tests

Flujo esperado:

1. obtener metadata/URL de media desde Graph API usando `provider_media_id`
2. descargar contenido con token configurado
3. validar tamaño, MIME y extensión esperada
4. entregar stream/contenido al storage driver

### Persistencia
La tabla `anticipo_certificado_archivos` ya tiene campos suficientes para el primer corte:

- `provider_file_id`
- `nombre_original`
- `mime_type`
- `extension`
- `size_bytes`
- `storage_disk`
- `storage_path`
- `hash_archivo`
- `estado_validacion`
- `motivo_rechazo`
- `metadata`

No se identifica una migración obligatoria para iniciar el driver local.

### Configuración
La selección de driver debe seguir centralizada en `config/medicina_laboral.php`:

- `MEDICINA_LABORAL_DRAFT_STORAGE_DRIVER=local`
- `MEDICINA_LABORAL_FINAL_STORAGE_DRIVER=local`

El default puede seguir siendo `metadata` hasta que el entorno tenga credenciales de WhatsApp y permisos de filesystem validados.

### Tests mínimos
El corte de implementación debería incluir:

- unit test de descarga fake + `LocalDraftAttachmentStorage`
- unit test de `LocalFinalAttachmentStorage`
- feature test del flujo de anticipo confirmando que `anticipo_certificado_archivos` queda con `storage_status = stored`
- test de rechazo por MIME inválido
- test de rechazo por tamaño excedido si la metadata de WhatsApp o la descarga permite medirlo

### Stop condition
Si no hay credenciales o contrato confiable para descargar media de WhatsApp, la implementación debe quedar limitada a diseño o a fakes de test. No se debe simular almacenamiento real con metadata-only bajo nombre de driver local.

---

## 28. Plan de implementación de estados de negocio

### Decisión
Avisos y anticipos/certificados deben nacer en estado técnico `inicial`.

Los estados con intervención de operador se implementarán sobre las entidades actuales:

- `Aviso`
- `AnticipoCertificado`

No se crea una entidad nueva para certificados.

### Migraciones recomendadas
Primer corte:

- agregar `avisos.estado` con default `inicial`
- agregar índice sobre `avisos.estado`
- backfill de avisos existentes a `inicial`
- cambiar default de `anticipos_certificado.estado` de `registrado` a `inicial`
- migrar anticipos existentes con estado `registrado` a `inicial`, salvo que ya tengan otro estado explícito futuro

Segundo corte, idealmente junto con admin:

- crear una tabla de historial de estados de negocio

Campos sugeridos:

- `id`
- `entidad_tipo` (`aviso` / `anticipo_certificado`)
- `entidad_id`
- `estado_anterior`
- `estado_nuevo`
- `origen` (`sistema` / `operador` / `migracion`)
- `actor_user_id` nullable, preparado para el futuro módulo admin
- `motivo` nullable
- `observacion` nullable
- `metadata` json nullable
- `created_at`

### Estados de avisos
Valores técnicos:

- `inicial`
- `observado`
- `verificado`
- `cancelado`
- `invalido`

### Estados de anticipos/certificados
Valores técnicos:

- `inicial`
- `observado`
- `vinculado`
- `cancelado`
- `invalido`

### Materialización desde conversación
Los servicios de materialización deben asignar estado explícito al crear registros:

- `AvisoService::createFromConversation()` debe crear avisos con `estado = inicial`
- `AnticipoCertificadoService::createFromConversation()` debe crear anticipos con `estado = inicial`

Esto evita depender solo del default de base de datos y deja el comportamiento claro en tests.

### Elegibilidad de aviso para cargar anticipo
Regla provisional hasta que exista workflow de operador:

- estados elegibles: todo aviso que no esté `cancelado` ni `invalido`
- estados bloqueantes: `cancelado`, `invalido`

Esta regla conserva compatibilidad con el flujo actual y permite que `inicial`, `observado` y `verificado` puedan recibir un anticipo mientras no exista una decisión de negocio más restrictiva.

### Ubicación de reglas
Las listas de estados y reglas de elegibilidad pueden comenzar en `config/medicina_laboral.php`, para mantener coherencia con los catálogos existentes y evitar hardcodear valores en validadores.

### Tests mínimos
El corte de implementación debería incluir:

- test de migración/modelo para avisos con estado default `inicial`
- test de `AvisoService` creando aviso con estado `inicial`
- test de `AnticipoCertificadoService` creando anticipo con estado `inicial`
- tests de `AvisoReferenciaValidator` bloqueando avisos `cancelado` e `invalido`
- test de historial de cambios de estado cuando se implemente el servicio de transición

---

## 29. Asociación de `AnticipoCertificado` con múltiples avisos

### Decisión
Un anticipo/certificado podrá estar asociado a N avisos usando la entidad actual `AnticipoCertificado`.

No se crea una entidad nueva `certificados`.

La conversación seguirá capturando un aviso inicial, como ocurre hoy. Las asociaciones adicionales se resolverán luego por intervención de operador desde el módulo administrativo o por una regla explícita futura.

### Motivo
El flujo conversacional actual ya valida un aviso previo y materializa el anticipo con una relación simple.

Para evolucionar sin romper compatibilidad:

- se conserva el flujo conversacional actual
- se agrega una tabla pivot para soportar múltiples avisos
- se mantiene `anticipos_certificado.aviso_id` transitoriamente como vínculo legacy/compatibilidad

### Implementación
Implementado en M5.3 del daily 2026-04-26.

Tabla pivot:

- `anticipo_certificado_aviso`

Campos implementados:

- `id`
- `anticipo_certificado_id`
- `aviso_id`
- `origen`
- `estado_vinculo` default `activo`
- `metadata` json nullable
- `created_at`
- `updated_at`

Restricciones e índices:

- unique compuesto por `anticipo_certificado_id` + `aviso_id`
- índice por `aviso_id`
- índice por `anticipo_certificado_id`
- índice por `estado_vinculo`
- foreign keys a `anticipos_certificado` y `avisos`

### Backfill
La migración `2026_04_26_000007_create_anticipo_certificado_aviso_table.php` puebla la pivot desde el vínculo actual:

- por cada `anticipos_certificado.aviso_id` no nulo, crear una fila pivot
- usar `origen = legacy_aviso_id`
- usar `estado_vinculo = activo`

### Escritura durante la transición
En el primer corte de implementación:

- `AnticipoCertificadoService::createFromConversation()` sigue escribiendo `aviso_id`
- además crea la fila pivot correspondiente con `origen = conversacion`

Esto mantiene compatibilidad con lecturas actuales y habilita las nuevas relaciones.

### Lecturas implementadas
Relaciones Eloquent:

- `AnticipoCertificado::avisos()` como `belongsToMany`
- `Aviso::anticiposCertificado()` como `belongsToMany` por pivot
- `AnticipoCertificado::aviso()` se conserva como relación legacy
- `Aviso::anticiposCertificadoLegacy()` se conserva como relación legacy por `aviso_id`

### Deprecación de `aviso_id`
No eliminar `anticipos_certificado.aviso_id` en el primer corte.

Plan recomendado:

1. crear pivot y backfill
2. escribir en ambos lugares desde servicios
3. migrar lecturas/reportes/admin a la pivot
4. recién después evaluar si `aviso_id` queda como compatibilidad, cache del primer aviso o se elimina

### Regla funcional vigente
Hasta nueva definición de negocio:

- el bot vincula el anticipo al aviso informado durante el flujo
- vínculos adicionales son manuales por operador/admin
- reglas automáticas de asociación múltiple quedan fuera del primer corte

### Tests cubiertos
El corte de implementación incluye:

- migración/backfill desde `anticipos_certificado.aviso_id`
- test de `AnticipoCertificadoService` creando pivot al confirmar anticipo
- test de relaciones Eloquent desde aviso hacia anticipos y desde anticipo hacia avisos
- test de unique compuesto para evitar vínculos duplicados

---

## 30. Relevamiento de logs y trazabilidad operativa

### Estado actual
El sistema combina dos mecanismos de trazabilidad:

- logs técnicos de Laravel mediante `Log::`
- eventos persistidos en `conversacion_eventos`

Los eventos persistidos son la fuente más apta para auditoría funcional porque quedan asociados a una conversación y sobreviven al ciclo de vida del proceso. Los logs de aplicación hoy cumplen un rol de debugging y operación inicial.

### Usos actuales de `Log::`

| Ubicación | Nivel | Uso actual | Riesgo principal |
| --- | --- | --- | --- |
| `WhatsappWebhookController::receive()` | `info` | registra payload completo del webhook entrante | alto: puede incluir teléfono, texto, adjuntos y payload crudo |
| `WhatsAppSender::dispatch()` | `warning` | credenciales faltantes | bajo |
| `WhatsAppSender::dispatch()` | `info` | registra payload saliente y destinatario | alto: puede incluir teléfono y texto enviado |
| `WhatsAppSender::dispatch()` | `info` | registra respuesta de WhatsApp | medio: puede incluir identificadores del proveedor |
| `WhatsAppSender::dispatch()` | `error` | registra error, payload y destinatario | alto: puede duplicar contenido sensible en errores |
| `ConversationInteractionService::logInteractionProcessed()` | `info` / `warning` | registra estado conversacional estructurado | medio: incluye participante/canal, pero no texto del mensaje |
| `ConversationTimeoutService` | `info` / `warning` | registra recordatorio y cancelación por inactividad | medio: incluye participante/canal |

### Eventos persistidos actuales
`conversacion_eventos` ya registra hechos relevantes para auditoría técnica:

- inicio de conversación
- mensaje entrante recibido
- cambios de estado
- validaciones fallidas
- reintentos
- cierres de conversación
- timeouts
- creación de aviso
- creación de anticipo/certificado

Estos eventos todavía no reemplazan todos los logs operativos, pero ya dan una base mejor para auditoría que los archivos de log.

### Gaps detectados

- No existe una política implementada de redacción/minimización de PII en logs.
- El webhook registra el payload completo del proveedor.
- El sender registra payloads salientes completos, incluyendo texto y destinatario.
- No hay convención documentada para separar logs de debugging, auditoría, operación y métricas.
- No hay correlación explícita estable fuera de `conversation_id`/`provider_message_id`.
- No hay política de retención de logs ni distinción de campos permitidos por ambiente.
- Las métricas se derivan indirectamente de eventos y contadores, pero no hay una capa de métricas propia.

### Estructura objetivo
Evolucionar hacia cuatro categorías:

1. `debug`: diagnóstico técnico de bajo nivel, deshabilitable o minimizado en producción.
2. `operacion`: salud del canal, envíos fallidos, timeouts y errores recuperables.
3. `auditoria`: hechos de negocio y cambios de estado persistidos en tablas, no sólo en logs.
4. `metricas`: contadores agregados sin PII para seguimiento operativo.

### Plan incremental recomendado

1. Definir política de datos sensibles (`LOG-001`) antes de ampliar logs o admin.
2. Introducir un servicio/helper de contexto seguro para logs conversacionales.
3. Reemplazar logs de payload completo por campos permitidos: `conversation_id`, `channel`, `provider_message_id`, `message_type`, `step_key`, `event_name`, `error_code`.
4. Mantener texto, teléfono completo, payload crudo y adjuntos fuera de logs de aplicación por defecto.
5. Promover auditoría funcional a eventos persistidos y, para admin, agregar auditoría específica de acciones administrativas.
6. Definir métricas agregadas desde eventos/contadores sin depender de parsear logs.

### Decisión del corte
No se implementa redacción parcial en este milestone.

La protección de datos sensibles queda pendiente explícito en `plan_dev/BACKLOG.md` (`LOG-001`) y debe resolverse antes de endurecer el módulo administrativo o ampliar la visibilidad operativa.
