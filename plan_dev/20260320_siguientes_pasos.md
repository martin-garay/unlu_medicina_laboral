# Planificación de siguientes pasos

> Nota histórica: este documento se conserva como referencia de transición. Los siguientes pasos activos deben definirse ahora en `plan_dev/daily/` y consolidarse en `plan_dev/STATUS.md`.

## Fecha
2026-03-20

## Objetivo

Definir los próximos pasos del proyecto en bloques chicos, revisables y orientados a commits cortos, para seguir avanzando sin perder control sobre los cambios.

---

## Estrategia de esta etapa

A partir del estado actual del proyecto, los próximos pasos no deben enfocarse en crear más base genérica, sino en:

- consolidar lo implementado
- cerrar huecos funcionales puntuales
- ampliar cobertura de tests
- preparar integraciones reales
- endurecer el sistema para escenarios más productivos

---

## Paso 1
### Confirmar el estado real del flujo de anticipo de certificado

#### Objetivo
Verificar exactamente hasta dónde está implementado hoy el flujo de anticipo y qué partes siguen parciales.

#### Revisar
- número de aviso
- validación de aviso elegible
- tipo de certificado
- adjuntos
- confirmación final
- alta real de `AnticipoCertificado`
- mensaje final
- persistencia de archivos asociados

#### Entregable
- lista concreta de qué está implementado
- lista concreta de huecos pendientes
- decisión de si el próximo desarrollo va sobre anticipo o sobre consolidación

---

## Paso 2
### Consolidar y cerrar el flujo de anticipo si quedó incompleto

#### Objetivo
Cerrar el flujo de anticipo de punta a punta si todavía quedó parcial.

#### Posibles tareas
- completar confirmación final
- completar alta real de `AnticipoCertificado`
- completar persistencia de archivos
- completar mensaje final
- mejorar validaciones del aviso asociado

#### Entregable
- flujo de anticipo funcional de punta a punta
- conversación asociada correctamente
- tests mínimos del flujo consolidado

---

## Paso 3
### Ampliar cobertura de tests sobre piezas críticas

#### Objetivo
Aumentar la red de seguridad del sistema antes de abrir integraciones reales.

#### Prioridad
- flujo de aviso
- flujo de anticipo
- servicios de alta de negocio
- validadores más críticos
- scheduler / inactividad si quedó flojo
- puntos de extensión de integraciones

#### Entregable
- nueva tanda de tests
- ejecución repetible y documentada
- detección más rápida de regresiones

---

## Paso 4
### Limpiar naming transicional y deudas chicas de arquitectura

#### Objetivo
Reducir ruido técnico que todavía haya quedado del camino incremental.

#### Posibles tareas
- renombrar claves transicionales que ya no correspondan
- simplificar handlers o servicios
- limpiar ramas viejas del controller si todavía queda algo
- consolidar uso de `MessageResolver`

#### Entregable
- código más claro
- menos deuda accidental
- mejor legibilidad para futuros prompts

---

## Paso 5
### Revisar y sincronizar diagramas con el estado real

#### Objetivo
Asegurar que la documentación visual en texto represente lo que hoy realmente hace el sistema.

#### Revisar
- Mermaid de aviso
- Mermaid de anticipo
- PlantUML de clases
- DBML de base de datos

#### Entregable
- diagramas actualizados
- documentación alineada con el código real

---

## Paso 6
### Consolidar la abstracción mock de Mapuche sin integrar todavía el sistema real

#### Objetivo
Mantener la identificación/validación del trabajador desacoplada y estable, sin avanzar todavía en una integración real con Mapuche hasta contar con definiciones funcionales y técnicas más precisas.

#### Alcance sugerido
- revisar la interfaz/contrato actual de identificación del trabajador
- revisar la implementación mock existente
- mejorar naming, claridad y puntos de extensión si hace falta
- asegurar que el flujo actual siga dependiendo de la abstracción y no de lógica hardcodeada
- documentar claramente qué datos debería proveer en el futuro una integración real:
  - legajo
  - nombre
  - sede
  - jornada laboral
- dejar explícito que la integración real queda pendiente por falta de definiciones

#### Qué NO hacer en este paso
- no implementar cliente real de Mapuche
- no integrar credenciales ni configuración productiva
- no acoplar handlers o servicios a una API externa
- no reemplazar el mock actual por una implementación incompleta

#### Entregable
- abstracción mock consolidada y clara
- documentación alineada sobre el alcance pendiente
- base lista para conectar Mapuche real cuando existan definiciones concretas

---

## Paso 7
### Definir estrategia real de email

#### Objetivo
Preparar o implementar notificaciones reales si negocio ya las necesita.

#### Alcance sugerido
- servicio desacoplado de email
- mensajes o templates asociados
- integración básica por entorno
- tests del servicio si aplica

#### Entregable
- punto de integración real para emails
- dominio no acoplado al proveedor

---

## Paso 8
### Definir storage definitivo de adjuntos

#### Objetivo
Cerrar la estrategia de persistencia real de archivos del anticipo.

#### Alcance sugerido
- interfaz de storage
- implementación local o S3 según necesidad
- metadata consistente
- manejo de errores

#### Entregable
- persistencia estable de archivos
- base lista para producción

---

## Paso 9
### Endurecimiento operativo del scheduler e inactividad

#### Objetivo
Mejorar comportamiento automático si el uso real lo justifica.

#### Posibles tareas
- jobs/colas para envíos automáticos
- política más fina por tipo de flujo
- ventana horaria
- tests adicionales del comando/scheduler

#### Entregable
- automatismos más robustos
- mejor control operativo

---

## Paso 10
### Preparación para uso más productivo

#### Objetivo
Dejar el sistema mejor parado para una etapa preproductiva o productiva.

#### Posibles tareas
- observabilidad
- logs más claros
- comandos operativos
- revisión de criterios de aceptación
- documentación final de soporte

#### Entregable
- base más operable
- menos riesgo de mantenimiento

---

## Posible paso futuro
### Evaluar consola interna o canal alternativo a WhatsApp

#### Objetivo
Analizar o implementar una interfaz propia para conversar con el sistema sin depender exclusivamente de WhatsApp, reutilizando el motor conversacional existente.

#### Alcance sugerido
- normalizar input interno por canal
- desacoplar envío saliente del `WhatsappWebhookController`
- exponer una API interna o consola de debug
- reutilizar trazabilidad, handlers, validadores y `MessageResolver`

#### Entregable
- diagnóstico de factibilidad o primera consola interna mínima

---

## Orden recomendado

1. confirmar estado real del anticipo
2. cerrar anticipo si hace falta
3. ampliar tests
4. limpiar naming/deuda chica
5. sincronizar diagramas
6. integración real con Mapuche
7. estrategia real de email
8. storage definitivo de adjuntos
9. endurecimiento del scheduler
10. preparación operativa

---

## Criterio de trabajo

A partir de este punto se recomienda:

- pasos chicos
- commits cortos
- tests dentro del mismo commit cuando corresponda
- documentación actualizada cuando cambie estructura o flujo
- diagramas sincronizados con cambios relevantes

---

## Conclusión

El proyecto ya está en una etapa donde conviene avanzar con consolidación e integración real, no con más estructura genérica.

Los próximos cambios deberían ser más precisos, medidos y acompañados por tests y documentación.
