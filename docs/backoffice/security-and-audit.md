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

## Contrato de auditoría administrativa

La auditoría administrativa debe vivir en una tabla propia, separada de los logs técnicos y de los eventos conversacionales.

El contrato mínimo del evento auditable es:

| Campo | Obligatorio | Descripción |
| --- | --- | --- |
| `id` | sí | Identificador interno del evento. |
| `actor_user_id` | no | Usuario administrativo que ejecutó la acción. Debe ser nullable para eventos de comandos, jobs o sistema. |
| `action` | sí | Acción controlada por la aplicación. |
| `origin` | sí | Origen operativo de la acción. |
| `auditable_type` | no | Clase o tipo de entidad afectada. |
| `auditable_id` | no | Identificador de la entidad afectada. |
| `before_values` | no | Estado anterior mínimo, estructurado y sin contenido sensible completo. |
| `after_values` | no | Estado nuevo mínimo, estructurado y sin contenido sensible completo. |
| `metadata` | no | Metadata auxiliar mínima para trazabilidad. |
| `created_at` | sí | Fecha y hora de registro del evento. |

El contrato no debe reemplazar las tablas de negocio. Debe guardar referencias, estados, códigos y cantidades suficientes para reconstruir qué acción administrativa ocurrió, sin copiar contenido médico ni payloads completos.

### Orígenes iniciales

Los valores iniciales permitidos para `origin` son:

- `filament`: acción iniciada desde el panel administrativo.
- `command`: acción ejecutada desde un comando Artisan.
- `job`: acción ejecutada desde un job o proceso asincrónico.
- `system`: acción generada por el sistema sin operador directo.

Si en el futuro se incorpora una API administrativa, debe agregarse como origen explícito y documentado antes de usarlo.

### Acciones iniciales esperadas

Las acciones iniciales deben nombrarse con formato estable `<recurso>.<accion>` y mantenerse controladas desde configuración o constantes de aplicación.

Acciones previstas para los próximos cortes:

- `roles.seeded`
- `permissions.seeded`
- `aviso.verified`
- `aviso.observed`
- `aviso.cancelled`
- `certificado.verified`
- `certificado.observed`
- `certificado.invalidated`
- `aviso_certificado.attached`
- `aviso_certificado.detached`
- `report.generated`
- `report.exported`
- `user_role.assigned`
- `user_role.revoked`

La auditoría de lectura o descarga de archivos médicos queda diferida a `I4`, porque depende de storage privado y de un mecanismo seguro de acceso. Cuando se implemente, las acciones esperadas serán:

- `certificado_file.viewed`
- `certificado_file.downloaded`

### Reglas de minimización

La auditoría administrativa debe guardar solo datos necesarios para trazabilidad operativa.

Queda prohibido registrar en auditoría:

- contenido médico completo
- binarios de archivos
- rutas públicas o temporales de descarga
- payloads completos de WhatsApp o de otros proveedores
- cuerpo completo de mensajes conversacionales
- copias completas de certificados, observaciones clínicas o adjuntos
- snapshots completos de modelos si contienen datos personales o médicos no necesarios

Los campos `before_values`, `after_values` y `metadata` deben construirse con listas explícitas de claves permitidas por acción. Como regla inicial, pueden incluir:

- identificadores internos
- estados anteriores y nuevos
- códigos de acción o error
- cantidades de archivos o asociaciones
- nombres de campos modificados cuando no expongan contenido sensible
- referencias a entidades persistidas

Si una acción requiere guardar más detalle para ser auditable, debe evaluarse contra `LOG-001` antes de ampliar el contrato.

### Servicio interno

Las capas administrativas no deben escribir eventos directamente desde Resources o controllers.

El punto de entrada inicial para registrar auditoría administrativa es:

- `App\Domain\Auditoria\Services\AuditoriaAdministrativaService`

Este servicio centraliza:

- validación de `action` no vacía
- validación de `origin` contra los orígenes permitidos
- actor administrativo nullable
- entidad auditable polimórfica opcional
- normalización de arrays vacíos a `null` para `before_values`, `after_values` y `metadata`

Las futuras Application Actions de avisos, certificados, asociaciones, reportes o usuarios deben depender de este servicio o de una fachada equivalente, no del modelo Eloquent directamente.

### Integración actual

La integración inicial registra la ejecución del seeder de roles y permisos del backoffice:

- `permissions.seeded`
- `roles.seeded`

Estos eventos usan `origin = command`, `actor_user_id = null` y metadata mínima con guard y conteos. No registran usuarios, passwords, payloads ni contenido sensible.

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

- nivel de detalle permitido en auditoría de lectura
