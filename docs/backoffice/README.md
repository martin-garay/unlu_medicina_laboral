# Backoffice

## Objetivo

Esta carpeta concentra la documentación técnica base para el administrador/backoffice del sistema de Medicina Laboral.

La decisión vigente es implementar el backoffice con Laravel + Filament, usando Filament como interfaz administrativa y manteniendo la lógica de negocio fuera de la capa visual.

## Estado actual

Estado despues de `I3` base:

- Filament está instalado.
- Existe panel base en `/admin`.
- Existe `App\Models\User` y tabla `users`.
- Existe autenticación administrativa mínima.
- El acceso al panel se restringe por permiso `backoffice.access`.
- La matriz inicial de roles y permisos está definida en `permissions.md`.
- Existe auditoría administrativa base con tabla, modelo, servicio e integración inicial.
- La relación N a N entre avisos y anticipos/certificados ya existe mediante `anticipo_certificado_aviso`.
- La entidad vigente para certificados médicos es `AnticipoCertificado`; no se crea una entidad principal nueva `certificados`.
- El storage real de archivos médicos sigue pendiente; hoy existe persistencia de metadata.

## Documentos

- `architecture.md`: arquitectura objetivo del backoffice y alcance inicial.
- `domain-separation.md`: separación entre Filament, Application Actions, Domain Services y Models.
- `filament-guidelines.md`: criterios de implementación de Resources, Pages, Widgets, Actions y RelationManagers.
- `implementation-plan.md`: milestones, tareas, validaciones y commits sugeridos para implementar el backoffice.
- `module-specs.md`: especificación fina de módulos administrativos, pantallas read-only y tests esperados.
- `permissions.md`: stack elegido, roles iniciales y matriz base de permisos.
- `security-and-audit.md`: seguridad, permisos y auditoría administrativa.
- `storage-and-sensitive-files.md`: estrategia esperada para archivos médicos sensibles.

## Principios base

- Filament es UI administrativa, no capa de reglas de negocio.
- Las acciones de negocio reutilizables se nombran como `Application Action`.
- No usar `UseCase` como convención principal de clases nuevas.
- Las reglas complejas deben vivir en Domain Services o Application Actions.
- Las operaciones sobre certificados médicos deben validar permisos y auditar escritura y lectura.
- Las futuras APIs deben poder reutilizar la misma lógica interna que Filament.

## Fuera de alcance inmediato

- crear Resources, Pages, Widgets o Actions reales
- implementar storage privado de certificados
- descargar o visualizar archivos médicos
- implementar State Machine
- implementar multi-tenancy o separación por sedes
- implementar permisos por campo o columna
- rediseñar la relación N a N aviso-certificado
