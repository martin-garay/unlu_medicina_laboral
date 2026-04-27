# Separación de responsabilidades

## Regla general

La arquitectura del backoffice separa interfaz administrativa, orquestación de aplicación, reglas de dominio y persistencia.

```text
Filament = interfaz administrativa
Application Action = acción de negocio reutilizable
Domain Service = regla de negocio reutilizable
Model = entidad, relaciones y persistencia Eloquent
```

## Vocabulario

No usar `UseCase` como convención principal de clases nuevas.

Usar `Application Action` para clases de aplicación que representan acciones concretas del sistema:

- `VerificarCertificadoAction`
- `ObservarCertificadoAction`
- `InvalidarCertificadoAction`
- `CancelarAvisoAction`
- `VerificarAvisoAction`
- `AsociarCertificadoAAvisoAction`
- `DesasociarCertificadoDeAvisoAction`
- `GenerarReporteCertificadosAction`
- `RegistrarMensajeEntranteAction`

No confundir:

- Filament Action: botón o interacción visual de UI.
- Application Action: operación de negocio reutilizable desde UI, API, Jobs, comandos o tests.

## Filament

Filament debe encargarse de:

- tablas
- formularios
- filtros
- acciones visuales
- botones
- badges
- confirmaciones
- navegación
- widgets
- dashboards
- mensajes de éxito y error
- RelationManagers
- visualización de relaciones entre entidades

Ejemplos válidos:

- mostrar listado de anticipos/certificados
- filtrar por estado, legajo, sede o fecha
- mostrar badges de estado
- pedir confirmación antes de verificar un certificado
- mostrar notificación al operador
- mostrar certificados asociados a un aviso
- mostrar avisos asociados a un certificado

Filament no debe decidir reglas complejas de negocio ni cambiar estados sensibles sin pasar por Application Actions.

## Application Actions

Las Application Actions pueden:

- abrir transacciones
- orquestar modelos y servicios
- validar permisos de aplicación
- aplicar reglas de dominio
- cambiar estados
- registrar auditoría
- disparar eventos
- enviar notificaciones
- ser llamadas desde Filament, API, comandos Artisan, Jobs o tests

Una Application Action debe tener una responsabilidad clara. Si la operación empieza a hacer muchas cosas no relacionadas, se debe dividir o delegar en Domain Services.

## Domain Services

Los Domain Services contienen reglas reutilizables, especialmente cuando involucran más de una entidad o una validación compleja.

Ejemplos esperados:

- `CertificadoValidationService`
- `AvisoEstadoService`
- `AnticipoCertificadoEligibilityService`
- `ConversacionResolverService`
- `ReporteCertificadosService`
- `CertificadoAvisoAssociationService`
- `CertificadoStorageService`
- `AuditoriaService`

## Models

Los modelos de Laravel deben concentrar:

- relaciones Eloquent
- casts
- scopes simples
- mutators/accessors simples
- reglas simples propias de la entidad
- métodos de transición simples si no tienen demasiada lógica

Si una regla involucra varias entidades, auditoría, permisos, storage o decisiones complejas, debe moverse a una Application Action o Domain Service.

## Excepciones de dominio

Las Application Actions y Domain Services deben lanzar excepciones específicas cuando una operación no pueda realizarse por una regla de negocio.

Evitar:

```php
throw new \Exception('Error');
```

Preferir excepciones específicas:

```text
CertificadoNoPuedeVerificarseException
CertificadoNoDisponibleException
AsociacionCertificadoAvisoInvalidaException
PermisoInsuficienteException
OperacionNoPermitidaException
```

Filament debe capturar estas excepciones y convertirlas en notificaciones claras para el operador, sin exponer detalles internos.

## Testing esperado

Cada Application Action relevante debe poder testearse sin depender de Filament.

Los tests deben cubrir:

- caso exitoso
- caso inválido por regla de negocio
- caso inválido por permisos o acceso
- auditoría generada
- efectos esperados sobre modelos y relaciones
- manejo correcto de excepciones de dominio

Los tests de Filament deben concentrarse en integración de UI administrativa, permisos visibles y disparo correcto de Application Actions.
