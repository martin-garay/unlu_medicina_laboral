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
`done`

### Resultado de ejecucion
- La daily de usuarios y roles quedo abierta como frente separado.
- Se confirma como proximo corte `P1 - Implementar UserResource read-only`.

### Validacion ejecutada
- `git diff --check`: sin errores.

### Commit sugerido
- `docs: abrir daily de usuarios y roles`

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
`done`

### Resultado de ejecucion
- Se creo `UserResource` read-only.
- Se agrego listado con nombre, email, roles, `is_admin` y fecha de creacion.
- Se agrego busqueda por nombre/email y filtro por rol.
- Se agrego detalle read-only sin exponer `password` ni `remember_token`.
- Se bloqueo create/edit/delete y acciones masivas.
- Se agregaron tests de permisos, listado, busqueda, filtro, detalle y bloqueo de escritura.

### Validacion ejecutada
- `make test`: `200 passed`, `831 assertions`.
- `git diff --check`: sin errores.

### Commit sugerido
- `feat: agregar usuarios read-only`

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
`blocked`

### Resultado de evaluacion
- Se frena antes de implementar escritura sobre usuarios.
- P2 requiere crear/editar usuarios, asignar roles y resetear password.
- Las reglas de la daily exigen auditar cambios criticos cuando exista accion de modificacion.
- La misma daily separa la auditoria de usuarios/roles en P5, por lo que no queda definido si la auditoria debe implementarse dentro de P2 o diferirse.
- Tambien falta definir el flujo de reset de password:
  - seteo manual por admin
  - password temporal generado
  - envio por email o entrega manual
  - obligar cambio posterior o no

### Bloqueo
- Definir antes de continuar:
  - si P2 debe incluir auditoria completa de creacion/edicion/roles/password o si P5 debe moverse antes de P2;
  - flujo esperado de reset de password;
  - protecciones exactas para impedir que el usuario actual se quite acceso y para no dejar el sistema sin `admin`.

### Validacion ejecutada
- No se modifico codigo en este milestone.
- `git diff --check`: sin errores.

### Proximo paso sugerido
- Reordenar la daily para definir/auditar Application Actions antes de habilitar escritura, o acotar P2 a una pantalla read-only extendida.

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
