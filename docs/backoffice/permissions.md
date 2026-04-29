# Permisos del backoffice

## Decisión de stack

El backoffice usará Spatie Laravel Permission como stack base de roles y permisos.

La integración debe usar el guard `web`, el mismo guard del panel Filament. Laravel Gates y Policies pueden utilizarse como capa de autorización sobre Resources, páginas y acciones, pero la fuente operativa de permisos será Spatie.

No se implementan permisos por campo o columna en esta etapa.

## Roles iniciales

### `admin`

Rol de administración completa del backoffice.

Puede:

- ingresar al backoffice
- ver dashboard
- administrar usuarios administrativos
- administrar roles y permisos
- ver avisos
- ver certificados médicos
- ver conversaciones
- ver historial de conversaciones
- ver auditoría
- ver reportes

### `auditor`

Rol de revisión y trazabilidad. No ejecuta acciones de estado ni administra usuarios.

Puede:

- ingresar al backoffice
- ver dashboard
- ver avisos
- ver certificados médicos
- ver conversaciones
- ver historial de conversaciones
- ver auditoría
- ver reportes

No puede:

- administrar usuarios
- administrar roles y permisos
- verificar, observar, invalidar o cancelar entidades de negocio

### `director`

Rol de lectura operativa y reportes. No administra usuarios, no accede a auditoría sensible y no ejecuta acciones de estado.

Puede:

- ingresar al backoffice
- ver dashboard
- ver avisos
- ver certificados médicos
- ver conversaciones
- ver historial de conversaciones
- ver reportes

No puede:

- administrar usuarios
- administrar roles y permisos
- ver auditoría administrativa sensible
- verificar, observar, invalidar o cancelar entidades de negocio

## Permisos base

| Permiso | Descripción | Admin | Auditor | Director |
| --- | --- | --- | --- | --- |
| `backoffice.access` | Ingresar al panel Filament | si | si | si |
| `dashboard.view` | Ver dashboard operativo | si | si | si |
| `users.view` | Ver usuarios administrativos | si | no | no |
| `users.manage` | Crear, editar o desactivar usuarios administrativos | si | no | no |
| `roles.view` | Ver roles y permisos | si | no | no |
| `roles.manage` | Administrar roles y permisos | si | no | no |
| `avisos.view` | Ver avisos de ausencia | si | si | si |
| `certificados.view` | Ver anticipos/certificados médicos | si | si | si |
| `conversaciones.view` | Ver listado de conversaciones | si | si | si |
| `conversaciones.historial.view` | Ver historial completo de mensajes y eventos de una conversación | si | si | si |
| `auditoria.view` | Ver auditoría administrativa | si | si | no |
| `reportes.view` | Ver reportes administrativos | si | si | si |

## Permisos diferidos

Los permisos de visualización y descarga de archivos médicos quedan diferidos a `I4 - Storage privado de certificados`, porque deben resolverse junto con:

- storage privado real
- mecanismo de acceso seguro
- auditoría de lectura
- reglas de acceso a archivos sensibles

No deben mezclarse con `certificados.view` hasta definir esa capa.

## Criterios de autorización

- Toda página o Resource de Filament debe validar permisos en backend.
- Ocultar botones o navegación por permiso mejora la UI, pero no reemplaza autorización backend.
- Las Application Actions futuras deben recibir un usuario autorizado o validar permisos explícitamente cuando ejecuten operaciones críticas.
- Las acciones de estado sobre avisos o certificados requieren permisos nuevos y no quedan habilitadas por esta matriz inicial.
- Cualquier operación sobre archivos médicos debe auditar lectura y descarga cuando se implemente `I4`.
- `conversaciones.view` habilita el listado; `conversaciones.historial.view` habilita la acción de ojo y la pantalla de detalle/historial. La URL directa del historial debe denegarse sin ese permiso.
