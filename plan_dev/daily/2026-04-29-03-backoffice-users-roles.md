# Daily Plan
## Fecha
2026-04-29

## Nombre
Backoffice users and roles

## Objetivo del dia
Planificar e implementar administración de usuarios, roles y permisos del backoffice en Filament, con salvaguardas para no romper el acceso administrativo.

## Estado actual resumido

- `App\Models\User` usa Spatie `HasRoles`.
- Existen permisos `users.view`, `users.manage`, `roles.view`, `roles.manage`.
- El rol `admin` sincroniza todos los permisos en desarrollo.
- No existe UI de gestión de usuarios/roles.

## Reglas de ejecucion

- Ejecutar milestones en orden.
- No permitir que un usuario se quite a sí mismo el acceso al panel.
- No permitir dejar el sistema sin usuarios `admin`.
- No borrar roles base en primera versión.
- Auditar cambios críticos cuando exista acción de modificación.
- Mantener lógica crítica en Application Actions, no en closures grandes de Filament.
- Cada milestone con código debe correr `make test` y `git diff --check`.

---

## P0
### ID
P0

### Nombre
Abrir daily de usuarios y roles

### Objetivo
Dejar ordenado el frente de administración de seguridad del backoffice.

### Validacion automatica obligatoria
- `git diff --check`

### Estado
`pending`

---

## P1
### ID
P1

### Nombre
Implementar UserResource read-only

### Objetivo
Agregar listado y detalle read-only de usuarios administrativos.

### Alcance
- `UserResource`
- permisos `users.view`
- listado de nombre, email, roles, `is_admin`, created_at
- búsqueda por nombre/email
- filtro por rol
- tests de permisos y listado

### Estado
`pending`

---

## P2
### ID
P2

### Nombre
Agregar gestión básica de usuarios

### Objetivo
Permitir crear/editar usuarios administrativos de forma controlada.

### Alcance
- permiso `users.manage`
- crear usuario
- editar nombre/email
- asignar roles
- resetear password
- Application Actions para cambios críticos
- tests de permisos y salvaguardas

### Stop conditions especificas
- si no queda claro cómo auditar cambios de roles/password, frenar y documentar.

### Estado
`pending`

---

## P3
### ID
P3

### Nombre
Implementar RoleResource read-only

### Objetivo
Agregar consulta de roles y permisos.

### Alcance
- `RoleResource`
- permiso `roles.view`
- listado de roles
- detalle de permisos
- cantidad de usuarios
- tests de permisos

### Estado
`pending`

---

## P4
### ID
P4

### Nombre
Agregar gestión controlada de permisos de rol

### Objetivo
Permitir administrar permisos de roles con protecciones.

### Alcance
- permiso `roles.manage`
- edición de permisos por grupos
- proteger roles base
- impedir quitar acceso crítico al último admin
- tests de salvaguardas

### Estado
`pending`

---

## P5
### ID
P5

### Nombre
Auditar cambios de usuarios y roles

### Objetivo
Registrar cambios administrativos relevantes en `auditoria_administrativa`.

### Alcance
- auditoría de creación/edición de usuario
- auditoría de cambios de roles
- auditoría de cambios de permisos
- tests del servicio de auditoría aplicado a estas acciones

### Estado
`pending`

