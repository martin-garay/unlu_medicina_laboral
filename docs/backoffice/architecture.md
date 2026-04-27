# Arquitectura del backoffice

## Decisión tecnológica

El administrador/backoffice se implementará con Laravel + Filament.

Filament se utilizará para:

- Resources
- Pages
- Widgets
- Forms
- Tables
- Filters
- Filament Actions visuales
- Dashboards administrativos
- RelationManagers

Filament no debe contener la lógica de negocio principal del sistema.

## Arquitectura objetivo

La arquitectura esperada es:

```text
Filament
    ↓
Application Actions
    ↓
Domain Services / Domain Rules
    ↓
Models / Persistence / Events / Audit
```

La regla práctica es que Filament presenta, valida entrada básica de UI y dispara operaciones. La decisión de negocio, las transacciones, las transiciones de estado, la auditoría y los efectos persistentes deben quedar en clases reutilizables fuera de Filament.

## Alcance inicial del backoffice

El backoffice debe preparar módulos para:

- gestión de usuarios administrativos
- gestión de roles y permisos
- dashboard operativo
- informes y estadísticas
- gestión de avisos de ausencia
- gestión de anticipos/certificados médicos
- asociación y desasociación entre avisos y anticipos/certificados
- histórico de anticipos, mensajes y conversaciones
- auditoría de acciones administrativas
- configuración administrativa acotada

## Estado del modelo aviso-certificado

El sistema ya tiene relación N a N entre avisos y anticipos/certificados:

- modelo de certificado vigente: `App\Models\AnticipoCertificado`
- modelo de aviso: `App\Models\Aviso`
- tabla pivot: `anticipo_certificado_aviso`
- relación desde certificado: `AnticipoCertificado::avisos()`
- relación desde aviso: `Aviso::anticiposCertificado()`
- vínculo legacy temporal: `anticipos_certificado.aviso_id`
- relación legacy desde aviso: `Aviso::anticiposCertificadoLegacy()`

La tabla pivot contiene:

- `anticipo_certificado_id`
- `aviso_id`
- `origen`
- `estado_vinculo`
- `metadata`
- timestamps

El backoffice debe usar la relación N a N como fuente para vistas y asociaciones nuevas. El campo `aviso_id` queda como compatibilidad temporal hasta que una decisión posterior defina si se elimina o queda como cache del primer aviso.

## Impacto de la relación N a N en Filament

En una vista de detalle de aviso se debe poder ver la lista de anticipos/certificados asociados mediante `Aviso::anticiposCertificado()`.

En una vista de detalle de anticipo/certificado se debe poder ver la lista de avisos asociados mediante `AnticipoCertificado::avisos()`.

Para Filament conviene modelarlo con RelationManagers:

- `AnticiposCertificadoRelationManager` dentro del Resource de avisos.
- `AvisosRelationManager` dentro del Resource de anticipos/certificados.

Las acciones de asociación y desasociación, si se habilitan, deben delegar en Application Actions y auditar:

- operador
- entidad origen
- entidad destino
- estado anterior del vínculo
- estado nuevo del vínculo
- motivo u observación
- origen de la acción

## Estructura sugerida

La estructura puede crecer de forma incremental. No es obligatorio crear todos estos directorios al inicio.

```text
app/
├── Filament/
│   ├── Resources/
│   ├── Pages/
│   ├── Widgets/
│   └── Actions/
│
├── Application/
│   ├── Avisos/
│   │   └── Actions/
│   ├── Certificados/
│   │   └── Actions/
│   ├── Anticipos/
│   │   └── Actions/
│   ├── Conversaciones/
│   │   └── Actions/
│   └── Reportes/
│       └── Actions/
│
├── Domain/
│   ├── Avisos/
│   │   ├── Enums/
│   │   └── Services/
│   ├── Certificados/
│   │   ├── Enums/
│   │   └── Services/
│   ├── Conversaciones/
│   │   ├── Enums/
│   │   └── Services/
│   └── Auditoria/
│       └── Services/
│
├── Models/
│
└── Infrastructure/
    ├── Storage/
    ├── Notifications/
    └── Integrations/
```

## Flujo conceptual

Ejemplo de verificación de certificado:

```text
Filament Action: botón "Verificar certificado"
    ↓
Application Action: VerificarCertificadoAction
    ↓
Domain Services: CertificadoValidationService, AuditoriaService
    ↓
Models: AnticipoCertificado, Aviso, User
```

Ejemplo de asociación:

```text
Filament Action: botón "Asociar aviso"
    ↓
Application Action: AsociarCertificadoAAvisoAction
    ↓
Domain Service: CertificadoAvisoAssociationService
    ↓
Models: AnticipoCertificado, Aviso, pivot anticipo_certificado_aviso
```

## Preparación para API futura

Una futura API REST o un frontend separado debe poder reutilizar las mismas Application Actions que use Filament.

```text
Filament Action
    -> VerificarCertificadoAction

API Controller futuro
    -> VerificarCertificadoAction
```

Por eso, la lógica de negocio no debe quedar encerrada en closures, callbacks o métodos privados de Resources de Filament.

## Fuera de alcance actual

- implementación de State Machine
- implementación de contexto de acción con IP/User-Agent
- implementación de multi-tenancy, tenant scopes o separación por sedes
- rediseño de la relación N a N entre avisos y certificados
- implementación concreta de Resources, Actions, migrations o dependencia Filament
