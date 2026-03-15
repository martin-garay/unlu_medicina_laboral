# Contexto funcional

## Objetivo

Este documento resume el contexto funcional del proyecto Medicina Laboral UNLu para que cualquier desarrollador, analista o agente de IA pueda entender rápidamente:

- qué problema se busca resolver
- quién interactúa con el sistema
- cuáles son los flujos principales
- qué reglas funcionales condicionan el diseño técnico
- por qué el sistema se está modelando de esta forma

No reemplaza a la documentación fuente, pero sí actúa como una síntesis de trabajo útil para implementación.

---

## Contexto general

El proyecto surge de la necesidad de contar con un sistema guiado, trazable y más accesible para registrar interacciones vinculadas a Medicina Laboral, especialmente relacionadas con:

- avisos de ausencia
- anticipos de certificados médicos

La solución priorizada evita, en esta etapa, una aplicación móvil propia o un sistema complejo de acceso web para el usuario final, y se apoya en un canal de uso cotidiano:

- **WhatsApp**

Esto permite construir una experiencia más simple para el/la trabajador/a y, al mismo tiempo, una base tecnológica ordenada para el área operativa.

---

## Problema actual que se busca mejorar

El proceso funcional requiere:

- capturar información del trabajador
- registrar el aviso de ausencia
- asociar luego un anticipo de certificado médico si corresponde
- controlar plazos y reglas
- dejar trazabilidad
- permitir revisión posterior por parte del área operativa

Si esta operatoria no está sistematizada adecuadamente, aparecen problemas como:

- falta de trazabilidad
- errores de carga
- dificultad para reconstruir qué pasó en cada caso
- datos incompletos o inconsistentes
- dependencia excesiva de procesos manuales
- dificultad para escalar el volumen operativo

---

## Solución priorizada

La solución priorizada en esta etapa es un **Sistema de Registro Guiado de Avisos de Ausencia**, con una arquitectura modular y crecimiento progresivo.

### Módulo 1
Canal conversacional por WhatsApp para:

- aviso de ausencia
- anticipo de certificado médico
- otras interacciones futuras

### Módulo 2
Interfaz operativa/administrativa para:

- control
- auditoría
- validación
- seguimiento
- reportes
- integraciones futuras

## Decisión actual de implementación

En esta etapa del MVP se prioriza principalmente el **Módulo 1**, dejando preparado el terreno para el futuro módulo operativo.

---

## Usuarios involucrados

## 1. Trabajador/a
Es quien interactúa con el chatbot a través de WhatsApp.

Puede necesitar:

- informar una ausencia
- anticipar un certificado médico
- responder preguntas guiadas del sistema
- confirmar datos antes de registrarlos

## 2. Área operativa / administrativa
No interactúa necesariamente con el chatbot, pero necesita:

- revisar registros
- contar con trazabilidad
- validar o auditar la información
- recibir luego un sistema más estructurado para gestión

## 3. Equipo de desarrollo
Necesita una base técnica mantenible, desacoplada y con documentación suficiente para evolucionar el sistema sin reescribirlo.

---

## Menú principal funcional

Según la documentación funcional analizada, el sistema contempla un menú principal con estas opciones:

1. Consultas
2. Aviso de ausencia
3. Anticipo de certificado médico

## Alcance actual priorizado

En esta primera etapa se priorizan:

- **Aviso de ausencia**
- **Anticipo de certificado médico**

La opción **Consultas** queda fuera del alcance inmediato, aunque el diseño debe permitir incorporarla más adelante.

---

## Flujo funcional: aviso de ausencia

El flujo de aviso de ausencia es uno de los procesos centrales del sistema.

## Objetivo del flujo

Permitir que el/la trabajador/a informe una ausencia de forma guiada, ordenada y trazable.

## Datos funcionales relevantes

El sistema debe solicitar, al menos:

- nombre completo
- número de legajo
- sede
- jornada laboral

Y además, para el aviso:

- período de ausentismo
- tipo de ausentismo
- motivo
- domicilio circunstancial
- observaciones adicionales
- datos del familiar cuando corresponda

## Confirmación final

Antes de registrar el aviso, el sistema debe mostrar un resumen y pedir confirmación al usuario.

## Resultado esperado

Una vez confirmado, el sistema debe registrar el aviso como una entidad de negocio separada de la conversación.

---

## Flujo funcional: anticipo de certificado médico

Este flujo permite registrar un anticipo de certificado médico asociado a un aviso previo.

## Regla central

No se debe poder registrar un anticipo sin un **aviso previo**.

## Datos funcionales relevantes

El sistema debe contemplar al menos:

- identificación del trabajador
- identificación del aviso previo
- tipo de certificado
- archivo adjunto del certificado
- confirmación final

## Reglas importantes

- debe existir un aviso elegible
- el plazo para registrar el anticipo puede ser configurable
- el tipo de certificado debe ser válido
- los formatos y tamaños de archivo deben ser controlables
- el proceso debe ser trazable

## Resultado esperado

Una vez confirmado, el sistema debe crear el anticipo como una entidad propia, vinculada al aviso correspondiente.

---

## Identificación del trabajador

La documentación funcional contempla una etapa de identificación del/la trabajador/a, solicitando datos como:

- nombre completo
- legajo
- sede
- jornada laboral

## Decisión actual de implementación

En esta primera etapa, la identificación real contra sistemas externos puede quedar:

- mockeada
- desacoplada detrás de interfaces o servicios reemplazables

Esto permite avanzar con la implementación del flujo sin depender todavía de integraciones no disponibles.

---

## Reglas funcionales relevantes para el diseño técnico

El análisis funcional deja varias reglas que impactan directamente en el diseño del sistema.

## 1. El anticipo requiere aviso previo
Esta regla obliga a separar claramente ambas entidades y a validar la existencia/elegibilidad del aviso antes de permitir el anticipo.

## 2. Cancelación disponible en todo momento
El usuario debe poder cancelar y volver al menú inicial en cualquier etapa.

## 3. La cancelación no debe borrar trazabilidad
Aunque el flujo se cancele, la evidencia técnica de lo ocurrido sigue siendo útil.

## 4. La inactividad forma parte del proceso
El sistema debe detectar abandono, enviar recordatorios y eventualmente cancelar el flujo por inactividad.

## 5. Hay múltiples validaciones por paso
Cada dato solicitado puede tener reglas propias, errores y reintentos.

## 6. La confirmación final es obligatoria
No debe generarse el registro de negocio sin una confirmación explícita del usuario.

## 7. El proceso debe ser auditable
Se necesita saber qué pasó, cuándo, con qué datos y en qué conversación.

---

## Por qué el diseño técnico separa conversación de negocio

Uno de los puntos más importantes del proyecto es que la **conversación** no equivale a un **aviso** ni a un **anticipo**.

## Conversación
Es la sesión técnica de interacción con el bot.  
Puede contener:

- mensajes válidos
- mensajes inválidos
- pasos incompletos
- reintentos
- cancelaciones
- expiraciones

## Aviso
Es el resultado de negocio del flujo de aviso, creado solo si el proceso termina correctamente.

## Anticipo
Es el resultado de negocio del flujo de certificado, creado solo si el proceso termina correctamente y asociado a un aviso previo.

## Motivo de esta separación

Permite:

- no ensuciar la base de negocio con intentos incompletos
- registrar toda la trazabilidad técnica
- soportar cancelación e inactividad sin perder información
- medir fricción del flujo
- mantener el sistema extensible

---

## Trazabilidad como requerimiento funcional

La trazabilidad no es solo una decisión técnica, sino una necesidad funcional del sistema.

El sistema debería permitir responder preguntas como:

- qué conversación originó un aviso
- qué conversación originó un anticipo
- cuántos mensajes necesitó el usuario
- cuántos errores cometió
- en qué paso se canceló el flujo
- si hubo inactividad
- qué mensajes se enviaron al usuario

Esto justifica la necesidad de modelar:

- conversaciones
- mensajes
- eventos
- asociaciones con las entidades de negocio

---

## Inactividad y automatismos

La documentación funcional menciona explícitamente comportamientos automáticos ante la falta de respuesta.

## Casos principales

- recordatorio automático
- segundo umbral de inactividad
- cancelación automática del flujo
- registro técnico de esos eventos

## Impacto en el diseño

Esto implica que el sistema no puede depender únicamente del webhook entrante.  
Necesita además procesos automáticos periódicos, razón por la cual se decidió usar:

- **Laravel Scheduler**

---

## Mensajes al usuario

La experiencia conversacional depende de muchos mensajes distintos:

- menú principal
- preguntas guiadas
- mensajes de error
- reintentos
- confirmaciones
- recordatorios
- cancelaciones
- mensajes finales

## Impacto en el diseño

Esto hace necesario desacoplar textos del código para que el sistema sea mantenible y adaptable.

Por eso se decidió:

- mensajes cortos en archivos de idioma
- mensajes largos en templates Blade
- parámetros del sistema en archivos de configuración

---

## Variabilidad futura

Aunque hoy se prioriza un MVP concreto, el contexto funcional sugiere que el sistema podría crecer en varias direcciones:

- consultas
- nuevos tipos de flujos
- más validaciones
- administración desde backoffice
- integraciones con sistemas externos
- notificaciones por email
- otros canales adicionales

## Impacto en el diseño

Por eso el proyecto no debe construirse como una solución rígida o enteramente hardcodeada.  
Necesita una base modular y extensible.

---

## Resumen funcional del proyecto

Hoy el proyecto debe entenderse así:

> Un sistema conversacional por WhatsApp que guía al/la trabajador/a en el registro de avisos de ausencia y anticipos de certificados médicos, con trazabilidad completa, validaciones por paso, confirmación explícita, automatismos por inactividad y separación entre conversación y entidades de negocio.

---

## Qué debería poder entender cualquier persona que lea este documento

Después de leer este documento debería quedar claro que:

- el proyecto no es solo un chatbot simple
- la conversación es una pieza técnica central
- el aviso y el anticipo son resultados de negocio separados
- la trazabilidad es obligatoria
- la cancelación y la inactividad son parte del comportamiento esperado
- el sistema debe crecer sin quedar atado a una implementación improvisada

---

## Relación con otros documentos

Este documento sirve como punto de entrada funcional y debe complementarse con:

- `docs/02-alcance-y-scope.md`
- `docs/03-arquitectura.md`
- `docs/04-modelo-de-datos.md`
- `docs/05-motor-de-conversacion.md`
- `docs/06-flujo-aviso-ausencia.md`
- `docs/07-flujo-anticipo-certificado.md`
- `docs/08-validaciones-y-reglas.md`
- `docs/09-scheduler-e-inactividad.md`
- `docs/10-mensajes-y-templates.md`
- `docs/12-decisiones-tecnicas.md`