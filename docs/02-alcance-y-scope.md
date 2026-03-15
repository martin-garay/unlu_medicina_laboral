# Alcance y scope

## Objetivo

Este documento deja asentado el alcance actual del proyecto Medicina Laboral UNLu, tomando como base:

- la documentación funcional compartida
- la propuesta de solución informativa
- las decisiones técnicas y funcionales ya acordadas durante el diseño inicial del MVP

El objetivo es alinear a desarrolladores, analistas y agentes de IA respecto de:

- qué entra en el proyecto hoy
- qué queda fuera por ahora
- qué se prioriza primero
- qué conceptos son centrales para la implementación

---

## Nombre de trabajo del proyecto

**Medicina Laboral WhatsApp MVP**

---

## Problema que busca resolver

El proyecto busca implementar un sistema guiado para que trabajadores/as puedan interactuar con Medicina Laboral a través de **WhatsApp**, realizando principalmente:

- aviso de ausencia
- anticipo de certificado médico

La solución debe permitir una interacción ordenada, trazable y progresiva, sin requerir una aplicación móvil propia en esta etapa.

---

## Alcance funcional actual

Tomando como referencia el menú principal del documento funcional, el sistema contempla:

1. Consultas
2. Aviso de ausencia
3. Anticipo de certificado médico

## Prioridad actual

Para esta etapa del MVP, se priorizan únicamente:

- **2. Aviso de ausencia**
- **3. Anticipo de certificado médico**

La opción:

- **1. Consultas**

queda fuera del alcance inmediato de implementación, aunque el diseño general debe permitir incorporarla más adelante.

---

## Canal principal

### Decisión
El canal principal del MVP será **WhatsApp Cloud API**.

### Motivo
- es consistente con la propuesta funcional priorizada
- reduce fricción para el usuario
- evita desarrollar app móvil propia en esta etapa
- permite validar rápidamente el flujo real del negocio

---

## Alcance funcional incluido

## 1. Menú principal conversacional

El sistema deberá presentar un menú principal conversacional que permita iniciar, al menos, los siguientes flujos:

- aviso de ausencia
- anticipo de certificado médico

Debe poder además:

- reiniciarse al comenzar una nueva conversación
- ser presentado nuevamente al cancelar un flujo
- ser mantenible sin textos hardcodeados

---

## 2. Flujo de aviso de ausencia

El sistema deberá poder guiar al usuario en la carga de un aviso de ausencia, incluyendo al menos:

- identificación del trabajador
- período de ausentismo
- tipo de ausentismo
- motivo
- domicilio circunstancial
- observaciones adicionales
- confirmación final
- registración efectiva
- mensaje final

### Datos a solicitar

Según la documentación funcional y el análisis realizado, deben contemplarse como mínimo:

- nombre completo
- número de legajo
- sede
- jornada laboral
- fecha desde
- fecha hasta
- tipo de ausentismo
- motivo
- domicilio circunstancial
- observaciones
- datos del familiar cuando corresponda

### Consideración actual
La identificación real del trabajador puede quedar temporalmente mockeada o desacoplada detrás de una interfaz.

---

## 3. Flujo de anticipo de certificado médico

El sistema deberá permitir registrar un anticipo de certificado médico, incluyendo al menos:

- identificación del trabajador
- identificación de un aviso previo
- tipo de certificado
- adjuntar archivo
- confirmación final
- registración efectiva
- mensaje final

### Regla central
No se debe permitir registrar un anticipo de certificado sin un aviso previo elegible.

### Parámetros a contemplar
El flujo debe dejar preparado el soporte para reglas configurables como:

- plazo máximo permitido desde el aviso
- máximo de intentos
- formatos permitidos
- cantidad máxima de archivos
- tamaño máximo de archivo

---

## 4. Motor de conversación

El sistema debe implementar un motor de conversación que permita:

- iniciar y recuperar conversaciones
- saber en qué paso está el usuario
- registrar mensajes entrantes y salientes
- distinguir mensajes válidos e inválidos
- contar intentos
- cancelar flujos
- cerrar conversaciones por inactividad
- dejar trazabilidad completa

### Concepto central
La conversación es una **unidad técnica de interacción**, no una entidad de negocio final.

---

## 5. Persistencia y trazabilidad

El sistema deberá registrar:

- conversaciones
- mensajes asociados a cada conversación
- eventos relevantes del flujo
- aviso generado
- anticipo generado
- archivos adjuntos del anticipo

### Requisito central
Debe poder saberse:

- qué conversación generó un aviso
- qué conversación generó un anticipo
- cuántos mensajes se intercambiaron
- cuántos fueron válidos o inválidos
- en qué paso hubo errores
- en qué paso se canceló o expiró un flujo

---

## 6. Cancelación manual

El sistema deberá permitir que el usuario cancele el flujo en cualquier momento mediante una opción como:

- “Cancelar y volver al menú inicial”

### Regla acordada
Cancelar implica:

- cerrar la conversación actual
- conservar mensajes y eventos
- no registrar aviso o anticipo si no corresponde
- no reutilizar esa conversación para un nuevo intento

Si luego el usuario vuelve a iniciar el proceso, debe abrirse una nueva conversación.

---

## 7. Inactividad y automatismos

El sistema deberá contemplar:

- recordatorio automático ante primer umbral de inactividad
- segundo umbral configurable
- cancelación automática del flujo
- registro técnico del evento

### Implementación acordada
Esto se resolverá con **Laravel Scheduler**.

---

## 8. Mensajes y templates

El sistema deberá organizar sus mensajes de manera desacoplada de la lógica de negocio:

- textos cortos en archivos de idioma
- mensajes largos o de confirmación en templates Blade
- parámetros en archivos de configuración

Esto aplica a:

- menú principal
- pedidos de datos
- errores de validación
- recordatorios
- cancelaciones
- confirmaciones finales
- mensajes finales

---

## 9. Validaciones extensibles

El sistema deberá implementar validaciones por paso del flujo de manera extensible.

Cada paso debería poder definir:

- dato esperado
- regla de validación
- cantidad máxima de intentos
- mensaje de error
- transición siguiente
- cancelación o derivación si corresponde

### Objetivo
Evitar una implementación monolítica difícil de mantener.

---

## 10. Estructura documental del proyecto

El proyecto deberá mantener documentación en:

- `README.md`
- `AGENTS.md`
- `docs/`

Con el objetivo de facilitar:

- onboarding de nuevos desarrolladores
- trabajo asistido por agentes de IA
- mantenimiento futuro
- alineación funcional y técnica

---

## Alcance técnico actual

Se considera dentro del alcance técnico inmediato:

- mejorar la base Laravel existente
- modelar conversaciones, mensajes y eventos
- implementar flujos conversacionales
- diseñar validaciones extensibles
- usar Laravel Scheduler para automatismos
- dejar mensajes y parámetros desacoplados
- preparar el terreno para integraciones futuras

---

## Fuera de alcance inmediato

Los siguientes puntos se consideran fuera de esta primera implementación prioritaria o no resueltos todavía:

- opción completa de “Consultas”
- integración real con SIU-Mapuche u otro sistema de personal
- panel administrativo completo
- edición dinámica de mensajes desde backoffice
- catálogo administrable por base de datos para todos los dominios
- envío real y definitivo de emails
- storage final productivo de archivos
- reporting avanzado
- dashboards operativos
- multi-canal
- app móvil propia

---

## Alcance técnico diferido pero previsto

Aunque no se implementen de inmediato, el diseño debe dejar preparado el terreno para:

- integración real de identificación del trabajador
- envío de notificaciones por email
- administración dinámica de tipos de certificado
- administración dinámica de mensajes
- soporte de nuevos flujos
- soporte de nuevos canales
- formalización de un adapter para payloads externos
- mayor desacople entre proveedor externo y dominio interno

---

## Entidades conceptuales centrales

El proyecto gira alrededor de estos conceptos:

### Conversación
Sesión técnica de interacción con el bot.

### Mensaje
Unidad individual de intercambio dentro de una conversación.

### Evento
Registro técnico o funcional relevante durante el flujo.

### Aviso
Entidad de negocio generada al finalizar correctamente el flujo de aviso de ausencia.

### Anticipo de certificado
Entidad de negocio generada al finalizar correctamente el flujo de anticipo y asociada a un aviso previo.

### Archivo adjunto
Documento o imagen asociada al anticipo de certificado.

---

## Reglas de negocio centrales ya asumidas

A esta altura del diseño, se consideran asumidas al menos estas reglas:

- el anticipo requiere aviso previo
- no se deben borrar conversaciones canceladas o expiradas
- una conversación cancelada no se reutiliza
- los mensajes válidos e inválidos deben quedar registrados
- la conversación no es equivalente al aviso
- la conversación no es equivalente al anticipo
- los automatismos por inactividad son parte del alcance
- los textos no deben quedar hardcodeados

---

## Priorización de implementación

## Etapa 1
Motor de conversación:
- conversaciones
- mensajes
- eventos
- estados básicos
- métricas e intentos

## Etapa 2
Flujo de aviso de ausencia:
- identificación
- período
- tipo de ausentismo
- motivo
- confirmación
- alta de aviso

## Etapa 3
Flujo de anticipo de certificado:
- aviso previo
- tipo de certificado
- archivos
- confirmación
- alta de anticipo

## Etapa 4
Automatismos:
- scheduler
- recordatorios
- cancelación por inactividad

## Etapa 5
Integraciones y endurecimiento:
- identificación real
- email
- storage
- catálogos más avanzados
- mayor desacople del proveedor

---

## Criterio de éxito del MVP

El MVP se considerará bien encaminado si permite:

- iniciar un flujo desde WhatsApp
- guiar al usuario paso a paso
- validar entradas
- cancelar o expirar sin perder trazabilidad
- registrar aviso correctamente
- registrar anticipo correctamente
- asociar cada resultado a su conversación efectiva
- sostener cambios futuros sin refactor estructural mayor

---

## Resumen ejecutivo del scope actual

Hoy el proyecto debe entenderse como:

> Un sistema conversacional sobre WhatsApp para registrar aviso de ausencia y anticipo de certificado médico, con fuerte foco en trazabilidad, cancelación, inactividad, mantenibilidad y separación entre conversación y entidades de negocio.

Ese es el scope vigente sobre el que deben apoyarse tanto la documentación como la implementación.