# Diagramas de despliegue

Esta carpeta contiene diagramas como código exclusivos del despliegue. No se
mezclan con los diagramas funcionales de `docs/diagrams/`.

- [`deployment-pipeline.mmd`](deployment-pipeline.mmd): release inmutable,
  activación, health check y rollback automático.

Los archivos Mermaid deben conservarse como fuente versionable. D2 valida sus
referencias; la generación de SVG puede incorporarse cuando exista una cadena
de render específica para documentación de deploy.
