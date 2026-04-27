# Plan de implementación incremental del backoffice

## Objetivo

Este plan ordena la implementación del backoffice con Laravel + Filament en cortes pequeños, verificables y commiteables.

La arquitectura base está documentada en:

- `docs/backoffice/architecture.md`
- `docs/backoffice/domain-separation.md`
- `docs/backoffice/filament-guidelines.md`
- `docs/backoffice/security-and-audit.md`
- `docs/backoffice/storage-and-sensitive-files.md`

## Reglas del plan

- Filament es interfaz administrativa.
- La lógica de negocio vive en Application Actions y Domain Services.
- No usar `UseCase` como convención para clases nuevas.
- No rediseñar la relación N a N `anticipo_certificado_aviso`.
- No implementar State Machine por ahora.
- No implementar multi-tenancy, tenant scopes ni separación por sedes.
- No implementar permisos por campo o columna.
- No almacenar certificados médicos en rutas públicas.
- Cada milestone debe cerrar con tests o validación explícita y actualización de estado.

## Estado de partida

- Filament no está instalado.
- No existe `app/Filament`.
- No existe estructura `app/Application` ni `app/Domain`.
- No existe modelo `User` ni tabla `users`.
- Spatie Laravel Permission no está instalado.
- La relación N a N aviso-certificado ya existe:
  - `AnticipoCertificado::avisos()`
  - `Aviso::anticiposCertificado()`
  - pivot `anticipo_certificado_aviso`
- `anticipos_certificado.aviso_id` sigue como compatibilidad temporal.
- El storage real de certificados sigue metadata-only.

## Milestone BO-M0 - Planificación y documentación base

### Objetivo
Dejar base documental suficiente antes de tocar runtime.

### Alcance
- arquitectura Filament
- separación de capas
- convención `Application Action`
- seguridad y auditoría
- storage privado
- impacto de relación N a N

### Estado
Cubierto por `BO-1` y este documento `BO-2`.

### Criterios de aceptación
- existe `docs/backoffice/`
- el plan incremental existe
- no hay cambios runtime

### Validación
- `git diff --check`

### Commits sugeridos
- `docs: agregar arquitectura base de backoffice`
- `docs: agregar plan incremental de backoffice`

## Milestone I1 - Base técnica de Filament

### Objetivo
Instalar y configurar la base mínima del panel administrativo sin desarrollar todavía módulos funcionales.

### Estado
`done` en `plan_dev/daily/2026-04-26-02-backoffice.md`.

### Resultado
- Filament `v5.6.1` instalado.
- Panel base disponible en `/admin`.
- Auth administrativa mínima agregada con `App\Models\User`, `config/auth.php` y migración de `users`.
- Acceso al panel restringido por `User::canAccessPanel()` usando `is_admin`.
- Estructuras base `app/Application` y `app/Domain` versionadas como placeholders.
- Docker ajustado para ejecutar Composer y Laravel con `ext-intl`.
- Sin Resources funcionales todavía.

### Alcance
- instalar Filament
- crear panel base
- crear estructura mínima `app/Filament`
- crear estructura vacía o placeholder para `app/Application` y `app/Domain`
- definir convención base de captura de excepciones de dominio desde Filament
- resolver prerequisito mínimo de usuario autenticable si Filament lo exige

### Dependencias
- BO-M0 cerrado
- decisión de si se permite crear `User` administrativo mínimo en este corte o se separa como primer commit de I2

### Tareas sugeridas

| Tarea | Áreas probables | Resultado esperado | Validación | Commit sugerido |
| --- | --- | --- | --- | --- |
| Verificar versión compatible de Filament | `composer.json`, docs oficiales de Filament | versión objetivo definida | `composer show` / revisión local | `chore: definir version objetivo de filament` |
| Instalar Filament | `composer.json`, `composer.lock`, config generada | dependencia instalada | `php artisan` sin errores | `chore: instalar filament` |
| Crear panel base | `app/Providers`, `app/Filament` | panel accesible en ruta definida | test HTTP básico o smoke manual | `chore: configurar panel base de filament` |
| Crear estructura interna | `app/Application`, `app/Domain` | carpetas base versionadas con clases reales o README técnicos | autoload sin errores | `chore: agregar estructura base de capas internas` |
| Documentar convenciones runtime | `docs/backoffice/` | convenciones de namespaces y errores | `git diff --check` | `docs: documentar convenciones runtime de backoffice` |

### Tests esperados
- `make test`
- smoke test del panel si el entorno lo permite
- test HTTP de acceso no autenticado si existe auth base

### Documentación a actualizar
- `docs/backoffice/implementation-plan.md`
- `plan_dev/STATUS.md`

### Criterios de aceptación
- Filament queda instalado o el bloqueo queda documentado.
- El panel base existe.
- No hay Resource funcional todavía.
- El acceso no autenticado no expone información administrativa.

### Riesgos / decisiones pendientes
- el proyecto no tiene `User`; puede obligar a adelantar una parte de I2.
- la instalación requiere red.

## Milestone I2 - Auth, usuarios, roles y permisos

### Objetivo
Implementar acceso administrativo con roles y permisos por módulo, acción y recurso.

### Alcance
- modelo y migración de usuarios si faltan
- decisión de stack de permisos
- roles iniciales
- permisos base
- Policies/Gates mínimos
- restricción de acceso al panel

### Dependencias
- I1 cerrado o decisión de fusionar auth mínima con I1
- decisión humana sobre Spatie Laravel Permission o implementación propia mínima
- matriz inicial para `admin`, `auditor`, `director`

### Tareas sugeridas

| Tarea | Áreas probables | Resultado esperado | Validación | Commit sugerido |
| --- | --- | --- | --- | --- |
| Relevar auth real | `app/Models`, `database/migrations`, `config/auth.php` | brecha confirmada | revisión + status | `docs: relevar auth actual de backoffice` |
| Crear usuario administrativo | `app/Models/User.php`, migrations, factories | autenticación base disponible | tests de modelo/auth | `feat: agregar usuarios administrativos` |
| Definir permisos | migrations/config/seeders | permisos por módulo y acción | tests de permisos | `feat: agregar permisos base de backoffice` |
| Restringir panel | provider de Filament, policies/gates | solo usuarios autorizados ingresan | tests HTTP | `feat: restringir acceso al panel de filament` |
| Documentar matriz | `docs/backoffice/` | matriz visible para revisión | `git diff --check` | `docs: documentar matriz inicial de permisos` |

### Tests esperados
- `make test`
- tests de acceso autorizado/denegado al panel
- tests de permisos sobre acciones críticas

### Documentación a actualizar
- `docs/backoffice/security-and-audit.md`
- `docs/backoffice/implementation-plan.md`

### Criterios de aceptación
- existe usuario administrativo autenticable.
- el panel no queda abierto.
- roles y permisos mínimos están documentados y testeados.

### Riesgos / decisiones pendientes
- falta confirmación de matriz funcional.
- `director` puede requerir definición de solo lectura vs acciones de estado.

## Milestone I3 - Auditoría administrativa base

### Objetivo
Crear una base reutilizable para auditar acciones administrativas y accesos sensibles.

### Alcance
- modelo y migración de auditoría
- `AuditoriaService`
- convención de evento de auditoría
- registro de acciones administrativas básicas
- preparación para auditoría de lectura de certificados

### Dependencias
- I2 cerrado
- usuario administrativo identificable

### Tareas sugeridas

| Tarea | Áreas probables | Resultado esperado | Validación | Commit sugerido |
| --- | --- | --- | --- | --- |
| Diseñar tabla de auditoría | migrations, docs | campos mínimos definidos | revisión + tests de migración | `feat: agregar tabla de auditoria administrativa` |
| Crear modelo y factory | `app/Models`, factories | eventos auditables persistibles | tests de modelo | `feat: agregar modelo de auditoria administrativa` |
| Crear servicio | `app/Domain/Auditoria/Services` | API interna para auditar | tests unitarios | `feat: agregar servicio de auditoria` |
| Registrar errores de dominio | capa Filament/Application | errores relevantes quedan auditados | tests de acción | `feat: auditar errores administrativos de dominio` |
| Documentar convención | `docs/backoffice/security-and-audit.md` | formato de evento estable | `git diff --check` | `docs: documentar eventos de auditoria` |

### Tests esperados
- tests de `AuditoriaService`
- tests de persistencia de evento
- tests de metadata mínima

### Criterios de aceptación
- toda acción futura puede auditarse con una API interna.
- auditoría no guarda contenido médico ni payloads sensibles completos.

### Riesgos / decisiones pendientes
- nivel de detalle permitido para auditoría de lectura.

## Milestone I4 - Storage privado de certificados

### Objetivo
Implementar almacenamiento y acceso privado para certificados médicos.

### Alcance
- disco privado
- servicio de storage
- endpoint o mecanismo seguro de descarga/visualización
- auditoría de lectura
- tests de acceso permitido y denegado

### Dependencias
- I2 para permisos
- I3 para auditoría
- decisión sobre Temporary URLs vs Controller Stream

### Tareas sugeridas

| Tarea | Áreas probables | Resultado esperado | Validación | Commit sugerido |
| --- | --- | --- | --- | --- |
| Configurar disco privado | `config/filesystems.php`, env docs | disco no público disponible | test storage fake | `feat: configurar storage privado de certificados` |
| Crear servicio de storage | `app/Domain/Certificados/Services` o `app/Infrastructure/Storage` | persistencia/lectura encapsulada | tests unitarios | `feat: agregar servicio de storage de certificados` |
| Migrar flujo metadata-only | servicios actuales de adjuntos | archivos reales guardados con metadata | tests de flujo | `feat: persistir archivos de certificados en storage privado` |
| Crear acceso seguro | controller/route o temporary URL service | descarga valida permisos | tests HTTP | `feat: agregar descarga segura de certificados` |
| Auditar lectura | `AuditoriaService` | visualización/descarga auditada | tests de auditoría | `feat: auditar acceso a archivos de certificados` |

### Tests esperados
- `make test`
- tests de storage fake
- tests HTTP de 403/200
- tests de auditoría por descarga

### Criterios de aceptación
- no hay exposición por `public/storage`.
- un operador sin permiso no accede.
- cada lectura sensible queda auditada.

### Riesgos / decisiones pendientes
- driver definitivo en producción.
- política de retención.

## Milestone I5 - Gestión de certificados médicos en Filament

### Objetivo
Implementar el Resource administrativo de certificados médicos usando `AnticipoCertificado`.

### Alcance
- Resource de `AnticipoCertificado`
- tablas, filtros, badges y detalle
- acceso seguro a adjuntos
- Application Actions de estado
- auditoría y tests

### Dependencias
- I1, I2, I3, I4
- definición de estados administrativos permitidos

### Tareas sugeridas

| Tarea | Áreas probables | Resultado esperado | Validación | Commit sugerido |
| --- | --- | --- | --- | --- |
| Crear Resource | `app/Filament/Resources` | listado básico de certificados | tests/smoke | `feat: agregar resource de certificados medicos` |
| Agregar filtros y badges | Resource/table columns | operación por estado/fecha/legajo | tests de query si aplica | `feat: agregar filtros y badges de certificados` |
| Crear vista de detalle | pages/infolists | operador ve datos y adjuntos | smoke test | `feat: agregar detalle de certificado medico` |
| Crear Actions de estado | `app/Application/Certificados/Actions` | verificar/observar/invalidar | tests de acciones | `feat: agregar acciones administrativas de certificados` |
| Integrar Filament Actions | Resource actions | botones delegan en Application Actions | tests Filament | `feat: integrar acciones de certificado en filament` |

### Tests esperados
- tests de Application Actions
- tests de permisos de Resource
- tests de auditoría
- smoke test de UI si aplica

### Criterios de aceptación
- la lógica de estado no vive en el Resource.
- los adjuntos usan acceso seguro.
- acciones críticas auditan.

### Riesgos / decisiones pendientes
- confirmar nomenclatura visual: "certificados médicos" sobre modelo `AnticipoCertificado`.
- confirmar transiciones permitidas.

## Milestone I6 - Gestión de avisos de ausencia en Filament

### Objetivo
Implementar el Resource administrativo de avisos.

### Alcance
- Resource de `Aviso`
- filtros, badges, detalle
- certificados asociados por relación N a N
- acciones administrativas de aviso
- auditoría y tests

### Dependencias
- I1, I2, I3
- I5 si se enlaza navegación completa hacia certificados

### Tareas sugeridas

| Tarea | Áreas probables | Resultado esperado | Validación | Commit sugerido |
| --- | --- | --- | --- | --- |
| Crear Resource | `app/Filament/Resources` | listado básico de avisos | smoke/test | `feat: agregar resource de avisos` |
| Agregar filtros y badges | Resource/table columns | filtros por estado/fecha/sede | tests de query si aplica | `feat: agregar filtros y badges de avisos` |
| Crear vista de detalle | pages/infolists | datos completos del aviso | smoke/test | `feat: agregar detalle de aviso` |
| Mostrar certificados relacionados | RelationManager o sección read-only | vínculos N a N visibles | test de relación | `feat: mostrar certificados asociados a avisos` |
| Crear acciones de aviso | `app/Application/Avisos/Actions` | verificar/observar/cancelar | tests de acciones | `feat: agregar acciones administrativas de avisos` |

### Tests esperados
- tests de Application Actions
- tests de permisos
- tests de auditoría
- tests de relación `Aviso::anticiposCertificado()`

### Criterios de aceptación
- los avisos se pueden revisar sin depender de `aviso_id` legacy.
- las acciones pasan por Application Actions.

### Riesgos / decisiones pendientes
- confirmar si avisos `observado` pueden recibir anticipo.

## Milestone I7 - Asociación N a N en backoffice

### Objetivo
Exponer y operar la relación N a N existente entre avisos y certificados.

### Alcance
- RelationManagers en ambos Resources
- asociación/desasociación si se habilita
- Application Actions específicas
- auditoría de cambios de vínculo

### Dependencias
- I5
- I6
- decisión sobre asociaciones manuales, automáticas o mixtas

### Tareas sugeridas

| Tarea | Áreas probables | Resultado esperado | Validación | Commit sugerido |
| --- | --- | --- | --- | --- |
| Crear RelationManager en Aviso | `AvisoResource` | certificados visibles desde aviso | tests/smoke | `feat: agregar relation manager de certificados en avisos` |
| Crear RelationManager en Certificado | `AnticipoCertificadoResource` | avisos visibles desde certificado | tests/smoke | `feat: agregar relation manager de avisos en certificados` |
| Crear acción de asociación | `app/Application/Certificados/Actions` | vínculo creado con reglas | tests de acción | `feat: agregar accion asociar certificado a aviso` |
| Crear acción de desasociación | `app/Application/Certificados/Actions` | vínculo desactivado o eliminado según decisión | tests de acción | `feat: agregar accion desasociar certificado de aviso` |
| Auditar vínculos | auditoría | cambios trazables | tests de auditoría | `feat: auditar cambios de asociacion aviso certificado` |

### Tests esperados
- tests de unique compuesto
- tests de asociación válida/inválida
- tests de auditoría
- tests de permisos

### Criterios de aceptación
- no se rediseña la pivot.
- no se escribe lógica compleja en RelationManagers.
- cada cambio queda auditado.

### Riesgos / decisiones pendientes
- definir si desasociar borra pivot o cambia `estado_vinculo`.

## Milestone I8 - Históricos de anticipos, mensajes y conversaciones

### Objetivo
Exponer trazabilidad histórica de conversaciones, mensajes y anticipos.

### Alcance
- Resources o Pages iniciales de solo lectura
- filtros por fecha, flujo, estado, legajo
- navegación desde aviso/certificado a conversación cuando exista
- timeline conversacional si aporta valor

### Dependencias
- I1, I2
- I3 si se auditan lecturas administrativas

### Tareas sugeridas

| Tarea | Áreas probables | Resultado esperado | Validación | Commit sugerido |
| --- | --- | --- | --- | --- |
| Crear Resource de conversaciones | `app/Filament/Resources` | listado read-only | tests/smoke | `feat: agregar resource de conversaciones` |
| Crear Resource de mensajes | `app/Filament/Resources` | mensajes filtrables | tests/smoke | `feat: agregar resource de mensajes de conversacion` |
| Crear timeline | page/infolist | vista secuencial legible | smoke/manual | `feat: agregar timeline conversacional` |
| Enlazar entidades de negocio | Resources existentes | navegación trazable | tests de relación | `feat: enlazar conversaciones con avisos y certificados` |

### Tests esperados
- tests de permisos read-only
- tests de filtros principales
- tests de no exposición de payload sensible si aplica

### Criterios de aceptación
- el backoffice permite reconstruir trazabilidad sin borrar ni alterar mensajes.
- los recursos son inicialmente de solo lectura.

### Riesgos / decisiones pendientes
- política de visualización de payload crudo.

## Milestone I9 - Dashboard operativo

### Objetivo
Crear dashboard inicial con métricas útiles y consultas acotadas.

### Alcance
- widgets simples
- métricas MVP sin PII innecesaria
- permisos por rol
- documentación de métricas

### Dependencias
- I1, I2
- Resources principales ya disponibles o consultas directas testeadas

### Tareas sugeridas

| Tarea | Áreas probables | Resultado esperado | Validación | Commit sugerido |
| --- | --- | --- | --- | --- |
| Definir métricas MVP | docs/backoffice | listado validable | revisión | `docs: documentar metricas iniciales de dashboard` |
| Crear widgets | `app/Filament/Widgets` | dashboard visible | smoke/test | `feat: agregar widgets operativos de backoffice` |
| Extraer consultas | Services si crecen | queries reutilizables | tests unitarios | `feat: agregar servicio de metricas operativas` |
| Aplicar permisos | Filament/provider/policies | visibilidad por rol | tests permisos | `feat: restringir widgets por permiso` |

### Tests esperados
- tests de métricas
- tests de permisos de widgets

### Criterios de aceptación
- dashboard no ejecuta consultas pesadas.
- no muestra datos sensibles innecesarios.

### Riesgos / decisiones pendientes
- volumen real de datos para performance.

## Milestone I10 - Informes, estadísticas y exportaciones

### Objetivo
Implementar reportes operativos y exportaciones seguras.

### Alcance
- reportes MVP
- Application Actions o servicios de reporte
- filtros
- exportaciones simples
- Queued Jobs para exportaciones pesadas
- storage privado de exports sensibles
- auditoría de generación y descarga

### Dependencias
- I2, I3, I4
- definición de reportes MVP

### Tareas sugeridas

| Tarea | Áreas probables | Resultado esperado | Validación | Commit sugerido |
| --- | --- | --- | --- | --- |
| Definir reportes MVP | docs/backoffice | alcance cerrado | revisión | `docs: documentar reportes mvp de backoffice` |
| Crear servicios de reporte | `app/Application/Reportes`, `app/Domain` | consultas testeables | tests unitarios | `feat: agregar servicios de reportes de backoffice` |
| Crear páginas de reporte | `app/Filament/Pages` | UI de filtros/resultados | smoke/test | `feat: agregar paginas de reportes` |
| Implementar exportación | Jobs/storage si aplica | archivo privado generado | tests de job/storage | `feat: agregar exportacion segura de reportes` |
| Auditar reportes | auditoría | generación/descarga trazada | tests auditoría | `feat: auditar reportes y exportaciones` |

### Tests esperados
- tests de filtros
- tests de generación de reporte
- tests de job si aplica
- tests de acceso privado a exportaciones

### Criterios de aceptación
- las exportaciones sensibles no son públicas.
- operaciones pesadas no bloquean UI.

### Riesgos / decisiones pendientes
- formato requerido de reportes.
- política de retención.

## Milestone I11 - Configuración administrativa

### Objetivo
Exponer parámetros administrables de forma controlada y auditable.

### Alcance
- relevar configuración actual
- definir parámetros administrables
- página o Resource de configuración
- permisos
- auditoría
- documentación de impacto

### Dependencias
- I2, I3
- decisión funcional sobre qué parámetros pueden modificarse desde UI

### Tareas sugeridas

| Tarea | Áreas probables | Resultado esperado | Validación | Commit sugerido |
| --- | --- | --- | --- | --- |
| Relevar configuración | `config/`, docs | candidatos identificados | revisión | `docs: relevar configuracion administrable` |
| Definir storage de settings | config/db según decisión | mecanismo definido | tests | `feat: agregar soporte de parametros administrables` |
| Crear UI | `app/Filament/Pages` | edición controlada | tests/smoke | `feat: agregar pagina de configuracion de backoffice` |
| Auditar cambios | auditoría | cambios trazados | tests auditoría | `feat: auditar cambios de configuracion` |

### Tests esperados
- tests de permisos
- tests de validación de parámetros
- tests de auditoría

### Criterios de aceptación
- no se pueden cambiar parámetros críticos sin permiso.
- cada cambio queda auditado.

### Riesgos / decisiones pendientes
- definir cuáles configuraciones dejan de ser solo código/env.

## Milestone I12 - Preparación para API futura

### Objetivo
Revisar que lo implementado pueda reutilizarse desde una API futura sin duplicar reglas.

### Alcance
- revisión de Application Actions
- revisión de Domain Services
- identificación de endpoints candidatos
- documentación de deuda de desacople

### Dependencias
- I5 a I11 según alcance implementado

### Tareas sugeridas

| Tarea | Áreas probables | Resultado esperado | Validación | Commit sugerido |
| --- | --- | --- | --- | --- |
| Auditar dependencias de Filament | `app/Application`, `app/Domain` | acciones desacopladas | tests + revisión | `refactor: desacoplar acciones de application de filament` |
| Documentar endpoints candidatos | `docs/backoffice` o `docs/` | mapa de API futura | `git diff --check` | `docs: documentar puntos de extension api` |
| Registrar deuda técnica | `plan_dev/BACKLOG.md` | pendientes visibles | revisión | `docs: registrar deuda de desacople para api futura` |

### Tests esperados
- tests existentes de Application Actions sin Filament
- `make test`

### Criterios de aceptación
- ninguna regla crítica queda atada exclusivamente a Resources de Filament.
- existe lista de endpoints candidatos y deuda conocida.

### Riesgos / decisiones pendientes
- prioridad real de API futura.

## Próximo milestone recomendado

El primer milestone de implementación posterior es `I1 - Base técnica de Filament`.

Antes de ejecutarlo conviene confirmar:

- si se puede crear el modelo/tabla `User` mínimo dentro de I1 o si debe abrirse como primer corte de I2
- si la instalación de dependencias puede usar red desde el entorno actual

Antes de `I2` deben confirmarse:

- stack de permisos: Spatie Laravel Permission o implementación propia mínima
- matriz inicial de permisos para `admin`, `auditor`, `director`

Antes de `I4` deben confirmarse:

- estrategia de acceso a archivos: Temporary URLs o Controller Stream
- driver definitivo de storage privado para producción

## Estrategia de commits

Los commits deben ser chicos y trazables. Separar cuando sea posible:

- dependencias y configuración
- migrations/modelos
- Application Actions
- Domain Services
- Resources de Filament
- tests
- documentación

Evitar commits que mezclen instalación, Resources completos, reglas de negocio, auditoría y documentación en un único corte.
