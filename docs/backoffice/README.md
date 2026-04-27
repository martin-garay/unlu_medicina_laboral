# Backoffice

## Objetivo

Esta carpeta concentra la documentación técnica base para el administrador/backoffice del sistema de Medicina Laboral.

La decisión vigente es implementar el backoffice con Laravel + Filament, usando Filament como interfaz administrativa y manteniendo la lógica de negocio fuera de la capa visual.

## Estado actual

Al momento de abrir este frente:

- Filament todavía no está instalado.
- No existe `app/Filament`.
- No existe estructura `app/Application` ni `app/Domain`.
- No existe todavía autenticación administrativa ni modelo `User`.
- La relación N a N entre avisos y anticipos/certificados ya existe mediante `anticipo_certificado_aviso`.
- La entidad vigente para certificados médicos es `AnticipoCertificado`; no se crea una entidad principal nueva `certificados`.
- El storage real de archivos médicos sigue pendiente; hoy existe persistencia de metadata.

## Documentos

- `architecture.md`: arquitectura objetivo del backoffice y alcance inicial.
- `domain-separation.md`: separación entre Filament, Application Actions, Domain Services y Models.
- `filament-guidelines.md`: criterios de implementación de Resources, Pages, Widgets, Actions y RelationManagers.
- `implementation-plan.md`: milestones, tareas, validaciones y commits sugeridos para implementar el backoffice.
- `security-and-audit.md`: seguridad, permisos y auditoría administrativa.
- `storage-and-sensitive-files.md`: estrategia esperada para archivos médicos sensibles.

## Principios base

- Filament es UI administrativa, no capa de reglas de negocio.
- Las acciones de negocio reutilizables se nombran como `Application Action`.
- No usar `UseCase` como convención principal de clases nuevas.
- Las reglas complejas deben vivir en Domain Services o Application Actions.
- Las operaciones sobre certificados médicos deben validar permisos y auditar escritura y lectura.
- Las futuras APIs deben poder reutilizar la misma lógica interna que Filament.

## Fuera de alcance de esta etapa

- instalar Filament
- crear Resources, Pages, Widgets o Actions reales
- crear migrations runtime
- implementar State Machine
- implementar multi-tenancy o separación por sedes
- implementar permisos por campo o columna
- rediseñar la relación N a N aviso-certificado
