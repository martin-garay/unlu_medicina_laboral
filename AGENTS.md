
---

## `AGENTS.md`

```md
# AGENTS.md

## Proyecto

Sistema MVP de Medicina Laboral UNLu implementado con Laravel + PostgreSQL + Docker, utilizando WhatsApp Cloud API como canal conversacional.

## Objetivo actual

Implementar una base sólida y mantenible para el motor de conversación y los flujos de negocio:

- aviso de ausencia
- anticipo de certificado médico

## Antes de tocar código

Leer obligatoriamente:

- `README.md`
- `docs/README.md`
- `docs/05-motor-de-conversacion.md`

Cuando existan, también leer:

- `docs/06-flujo-aviso-ausencia.md`
- `docs/07-flujo-anticipo-certificado.md`
- `docs/08-validaciones-y-reglas.md`
- `docs/09-scheduler-e-inactividad.md`
- `docs/diagrams/README.md`

## Reglas de diseño

- No hardcodear textos en el código.
  - Usar `lang/es/*.php`
- No hardcodear parámetros configurables.
  - Usar `config/*.php`
- Controllers livianos.
- La lógica de negocio debe vivir en servicios o handlers.
- Toda interacción de usuario debe quedar asociada a una conversación.
- No borrar conversaciones ni mensajes cancelados, expirados o fallidos.
- Una conversación es una unidad técnica de trazabilidad.
- Un aviso es una entidad de negocio separada.
- Un anticipo de certificado es una entidad de negocio separada y requiere aviso previo.
- Los automatismos de inactividad deben resolverse con Laravel Scheduler.
- Los mensajes largos deben vivir en templates Blade.
- Los diagramas versionables viven en `docs/diagrams/`.
- Si cambian flujos o estructura relevante, actualizar los diagramas afectados.

## Criterios de modelado

### Conversación
Representa una sesión viva entre el usuario y el bot.

Debe soportar:

- estado actual
- tipo de flujo
- intentos
- timestamps
- timeout
- cierre por cancelación
- cierre por inactividad
- historial de mensajes y eventos

### Mensajes
Cada mensaje entrante y saliente debe quedar persistido y asociado a una conversación.

Registrar cuando sea posible:

- dirección (`in` / `out`)
- tipo de mensaje
- contenido
- payload crudo
- validez
- motivo de invalidez
- paso del flujo

### Eventos
Registrar eventos técnicos y de trazabilidad, por ejemplo:

- cambio de estado
- validación fallida
- recordatorio por inactividad
- cancelación automática
- cancelación manual
- creación de aviso
- creación de anticipo

## Convenciones de implementación

- Preferir componentes pequeños y extensibles.
- Evitar `switch` gigantes para manejar flujos completos.
- Diseñar los pasos del flujo para que cada uno tenga:
  - dato esperado
  - validación
  - respuesta de éxito
  - respuesta de error
  - cantidad de intentos
  - transición al siguiente paso
- Separar:
  - normalización de mensajes
  - validación
  - persistencia
  - respuesta
  - materialización de entidades de negocio

## Integraciones futuras

La identificación real del trabajador y otras integraciones externas deben quedar desacopladas detrás de interfaces o servicios mockeables.

## Qué no asumir

- No asumir que todo mensaje recibido es texto.
- No asumir que una conversación debe derivar siempre en un aviso.
- No asumir que una conversación cancelada debe reutilizarse.
- No asumir que los textos o catálogos serán fijos en el tiempo.

## Meta técnica de corto plazo

Dejar el proyecto preparado para:

- trazabilidad completa
- mantenimiento simple
- incorporación de nuevas validaciones
- incorporación de nuevos pasos o subflujos
- administración futura de mensajes y parámetros
- documentación visual viva mantenible desde Git y por agentes
