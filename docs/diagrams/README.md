# Diagramas como código

## Objetivo

Este directorio concentra la documentación visual del proyecto en formatos de texto versionables.

La convención busca que los diagramas:

- se puedan revisar en Git y en PRs
- se puedan editar por desarrolladores o por agentes como Codex
- se puedan renderizar dinámicamente sin guardar binarios en el repo
- formen parte de la documentación viva del proyecto

## Formatos adoptados

### Flujos conversacionales

Usar **Mermaid**.

Ubicación:

- `docs/diagrams/flows/*.mmd`

Se usa para:

- menú principal
- recorridos conversacionales
- pasos principales
- confirmaciones, cancelaciones y desvíos relevantes

### Diagramas de clases

Usar **PlantUML**.

Ubicación:

- `docs/diagrams/classes/*.puml`

Se usa para:

- visión estructural del motor conversacional
- relaciones entre modelos, servicios y contratos
- piezas importantes del dominio y de la orquestación

### Diagramas de base de datos

Usar **DBML**.

Ubicación:

- `docs/diagrams/db/*.dbml`

Se usa para:

- esquema actual de tablas
- relaciones principales
- documentación textual del modelo persistente

## Opcional a futuro

Si el proyecto necesita más adelante documentación de arquitectura general de más alto nivel, puede evaluarse **Structurizr DSL**.

No forma parte de la convención inicial ni se implementa en esta etapa.

## Por qué esta decisión

- texto versionable en Git
- diff legible en PRs
- mantenimiento simple
- buena compatibilidad con prompts y agentes de IA
- renderizado dinámico fuera del repo cuando haga falta
- alineación con una documentación que debe evolucionar junto al código

## Criterios de uso

- Actualizar los diagramas cuando cambian flujos conversacionales relevantes.
- Actualizar los diagramas cuando cambian clases, contratos o relaciones estructurales importantes.
- Actualizar los diagramas de base de datos cuando cambian tablas, columnas clave o relaciones principales.
- Regenerar los SVG derivados cuando cambian los fuentes de Mermaid o PlantUML.
- No inventar precisión que el código todavía no tiene.
- Si una pieza todavía es futura o parcial, dejarlo explícito en el diagrama o en una nota cercana.

## Relación con futuros prompts

Estos archivos forman parte de la documentación viva del proyecto.

Los próximos prompts a Codex o a otros agentes deben considerarlos como referencia visual en texto junto con:

- `README.md`
- `docs/README.md`
- documentos funcionales y técnicos de `docs/`

## Estructura inicial

- `flows/`: flujos conversacionales en Mermaid
- `classes/`: diagramas de clases y relaciones conceptuales en PlantUML
- `db/`: esquema de base de datos en DBML
- `rendered/`: SVGs derivados listos para lectura rápida

## Renderizado local recomendado

Se adopta una estrategia liviana basada en Docker para no exigir instalaciones locales de Node o Java fuera del flujo habitual del proyecto.

Comandos:

```bash
make diagrams
```

```bash
make diagrams-check
```

Esto genera:

- `docs/diagrams/rendered/flows/*.svg`
- `docs/diagrams/rendered/flows/*.png`
- `docs/diagrams/rendered/classes/*.svg`

## Alcance del renderizado actual

- Mermaid: se generan SVG y PNG
- PlantUML: se genera SVG
- DBML: se mantiene como fuente de verdad textual y no se exporta a imagen estática en esta etapa

## Compatibilidad de visualización

Los SVG de Mermaid pueden incluir elementos que algunos visores embebidos de IDE no muestran correctamente.

Por eso, para los flujos se generan también PNG derivados en `docs/diagrams/rendered/flows/`, pensados como formato de lectura rápida y compatible.

## Nota de alcance actual

En el estado actual del repo:

- el flujo de aviso estándar llega a registración efectiva
- el flujo de anticipo todavía queda incompleto en confirmación/materialización
- las tablas de anticipo están documentadas como proyección futura y no existen todavía en las migraciones actuales

Los diagramas iniciales reflejan esa situación de manera explícita.
