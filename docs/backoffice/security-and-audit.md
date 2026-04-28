# Seguridad y auditoría

## Principio base

El backoffice opera información laboral y médica sensible. Seguridad, permisos, auditoría y testing no son mejoras opcionales: son requisitos base del diseño.

## Autenticación y permisos

El backoffice debe contemplar:

- autenticación de usuarios administrativos
- roles y permisos
- Policies/Gates de Laravel
- restricción por acción y recurso
- control de acceso a certificados médicos
- validaciones backend además de validaciones de UI
- principio de menor privilegio

No se implementan por ahora permisos por campo o columna.

El stack de permisos elegido para el backoffice es Spatie Laravel Permission, usando el guard `web`.

La matriz inicial está documentada en `docs/backoffice/permissions.md` y define:

- rol `admin` para administración completa
- rol `auditor` para lectura operativa y auditoría sin acciones de estado
- rol `director` para lectura operativa y reportes sin auditoría sensible

Los permisos de acceso a archivos médicos quedan diferidos a `I4`, junto con storage privado y auditoría de lectura.

## Acciones administrativas auditables

Toda acción administrativa relevante debería registrar:

- usuario u operador que ejecutó la acción
- fecha y hora
- entidad afectada
- acción realizada
- estado anterior
- estado nuevo
- motivo u observación, si corresponde
- origen de la acción: Filament, API, Job, comando, etc.
- metadata útil para trazabilidad

Ejemplos:

- verificación de certificado
- observación o invalidación de certificado
- cancelación o verificación de aviso
- asociación o desasociación aviso-certificado
- descarga de archivo médico
- exportación de información sensible
- cambios de roles o permisos

## Auditoría de lectura

Los certificados médicos requieren auditoría de lectura, no solo auditoría de escritura.

Deben auditarse:

- visualización de certificados médicos
- descarga de certificados médicos
- apertura de archivos sensibles
- exportación de información sensible

La auditoría de lectura debe permitir responder quién accedió a qué información, cuándo y desde qué origen operativo.

## Asociación aviso-certificado

Las asociaciones sobre la pivot `anticipo_certificado_aviso` deben auditarse cuando se hagan desde el backoffice.

Datos mínimos sugeridos:

- `anticipo_certificado_id`
- `aviso_id`
- `estado_vinculo` anterior y nuevo
- `origen`
- operador
- motivo u observación
- timestamp

## Logs y datos sensibles

Los logs operativos no deben exponer datos médicos ni archivos. La política fina de datos sensibles en logs está registrada como pendiente `LOG-001` en `plan_dev/BACKLOG.md`.

Hasta resolver `LOG-001`, cualquier implementación nueva debe minimizar datos personales y médicos en logs, evitando payloads completos y contenido de archivos.

## Excepciones y errores

Las excepciones de dominio deben ser específicas y controladas. Filament debe mostrar mensajes administrativos claros sin exponer detalles internos.

Errores técnicos inesperados deben quedar trazados para soporte, pero sin filtrar datos sensibles.

## Decisiones pendientes

- formato final de tabla o mecanismo de auditoría administrativa
- nivel de detalle permitido en auditoría de lectura
