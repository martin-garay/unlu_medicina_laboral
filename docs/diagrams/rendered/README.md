# Diagramas renderizados

Este directorio contiene artefactos derivados a partir de los diagramas fuente de `docs/diagrams/`.

## Contenido esperado

- `flows/*.svg`: generados desde Mermaid
- `classes/*.svg`: generados desde PlantUML

## Regla de mantenimiento

- No editar estos archivos manualmente.
- Regenerarlos con `make diagrams`.
- Cuando cambien los diagramas fuente, los SVG derivados deben refrescarse dentro del mismo cambio.

## Nota sobre DBML

Por ahora no se genera una imagen estática desde `docs/diagrams/db/*.dbml`.

El archivo DBML sigue siendo la fuente de verdad del esquema, y su visualización puede resolverse con herramientas compatibles como dbdiagram.
