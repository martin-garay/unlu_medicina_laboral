# Testing y criterios

## Objetivo

Este documento define la estrategia de testing del proyecto Medicina Laboral WhatsApp MVP.

Se incorpora formalmente después de completar la base funcional principal de los flujos conversacionales porque, a partir de ese punto, el riesgo de regresión pasa a ser más costoso que el costo de agregar cobertura incremental.

## Qué riesgos busca reducir

- regresiones en transiciones de pasos
- validaciones inconsistentes entre handlers
- roturas silenciosas en servicios de conversación
- desalineación entre flujos y reglas de negocio ya implementadas
- cambios futuros sin evidencia verificable de comportamiento

## Cómo acompaña la arquitectura actual

La arquitectura conversacional ya separa:

- handlers
- validadores
- resolvedores
- servicios de conversación
- materialización de negocio

Eso permite empezar a testear por piezas relativamente estables y de responsabilidad clara, sin depender primero de tests pesados de controller.

## Tipos de tests esperados

### 1. Tests unitarios

Prioridad principal en esta etapa.

Aplican a:

- `StepResult`
- validadores
- handlers pequeños
- `ConversationFlowResolver`
- servicios con lógica contenida

### 2. Tests de integración livianos

Aplican cuando hay interacción real entre piezas internas del proyecto, por ejemplo:

- servicios que persisten conversación o entidades de negocio
- materialización de `Aviso` o futuro `AnticipoCertificado`
- flujos puntuales que convenga validar sobre Laravel y base de datos

### 3. Qué no entra todavía

Por ahora no es prioridad:

- E2E completos contra WhatsApp Cloud API
- pruebas de browser
- pipelines complejos de CI/CD
- mocks extensivos de sistemas externos aún no integrados

## Qué se debe testear primero

Orden sugerido:

1. validadores
2. `StepResult`
3. `ConversationFlowResolver`
4. handlers con branching no trivial
5. servicios base de conversación
6. servicios de materialización de negocio ya existentes
7. casos críticos de flujo en la capa hoy más estable

## Justificación de esa prioridad

- los validadores y resultados de paso son piezas pequeñas, estables y baratas de cubrir
- los handlers concentran decisiones funcionales importantes
- los servicios de conversación y de negocio sostienen trazabilidad y persistencia
- testear primero esas capas da valor alto sin acoplarse demasiado al webhook

## Formato esperado

### Ubicación

- tests unitarios en `tests/Unit`
- tests de integración livianos en `tests/Feature`

### Convención de nombres

Usar nombres descriptivos orientados a comportamiento, por ejemplo:

- `DateInputValidatorTest`
- `ConversationFlowResolverTest`
- `AvisoServiceTest`
- `CertificadoNumeroAvisoStepHandlerTest`

### Granularidad recomendada

- un test debe cubrir una responsabilidad clara
- preferir casos chicos y legibles
- evitar tests gigantes que mezclen múltiples decisiones no relacionadas

### Cuándo usar mocks o fakes

Usarlos cuando:

- una dependencia externa no forma parte del comportamiento bajo prueba
- la dependencia hace el test más frágil o más lento sin aportar señal

Preferir fakes/mocks para:

- logs
- envío a WhatsApp
- servicios externos futuros

### Cuándo evitarlos

No abusar de mocks en piezas simples del dominio o del flujo cuando se puede probar comportamiento real con menor complejidad.

### Qué evitar

- tests demasiado acoplados al controller
- asserts sobre detalles irrelevantes de implementación
- tests que solo replican el código internamente sin verificar comportamiento observable

## Criterio de aceptación

Un cambio se considera aceptablemente testeado cuando cumple, según corresponda, con estas reglas:

- lógica nueva relevante llega con tests
- bugfix relevante incluye test que cubre el caso corregido
- validador nuevo tiene cobertura unitaria
- handler con branching no trivial cubre al menos camino válido y errores principales
- servicio con persistencia o materialización de negocio cubre el comportamiento crítico

Si un cambio no incluye tests, la ausencia debe quedar justificada explícitamente.

## Política de proyecto desde este punto

Desde la incorporación formal de esta estrategia:

- los pasos siguientes del plan deben incluir tests en el mismo commit cuando corresponda
- no se debe seguir acumulando lógica nueva sin cobertura mínima razonable
- “lo testeamos después” deja de ser la regla por defecto

## Infraestructura mínima actual

La base mínima esperada del proyecto para testing es:

- `phpunit.xml`
- carpeta `tests/`
- `tests/TestCase.php`
- comando repetible para ejecución local

## Ejecución automática o repetible

### Comando principal recomendado

```bash
docker-compose exec app php artisan test
```

### Atajo recomendado

```bash
make test
```

### Punto natural de integración automática

El mismo comando debe ser el candidato natural para:

- hooks locales si el equipo los agrega
- CI futuro
- validación previa a merge

## Forma mínima viable en esta etapa

No hace falta todavía una pipeline completa.

Sí hace falta que:

- correr tests sea simple
- el comando sea único y repetible
- el repositorio tenga una base preparada para crecer con cobertura incremental
