# Memoria técnica selectiva

Propósito: evitar redescubrir ubicaciones, contratos estables y trampas conocidas.
No es diario, backlog, especificación ni registro alternativo de decisiones.

## Lectura

1. Leer [INDEX](INDEX.md).
2. Elegir dominio; cargar sólo entradas relacionadas.
3. Buscar (`rg`) en la fuente enlazada y leer el fragmento necesario.
4. Si memoria y código/tests difieren, verificar la fuente y corregir la memoria;
   no convertir una nota desactualizada en un requisito.

El Lead entrega a cada worker sólo ficha, contrato y memoria relevante.
Los archivos ya consultados se reutilizan en la sesión; releer si cambian.
Arquitectura/backend/frontend/DB/testing/gotchas tienen notas breves propias.
Convenciones, desarrollo, deploy y decisiones ya tienen fuentes: el índice enlaza
hacia ellas, sin archivos duplicados. `archive/` se crea sólo al necesitarlo.

## Escritura

Workers reportan `Memory candidates`; sólo el Lead consolida y decide:
`IGNORE`, `UPDATE_EXISTING` o `ADD`. Priorizar actualizar una entrada existente.
Preguntar al cierre qué conocimiento evitaría investigación futura; si nada,
no editar. Cada nota debe tener fuente verificable y fecha de revisión.

Guardar sólo conocimiento reusable, restricciones, ubicaciones no obvias,
dependencias, workarounds comprobados y decisiones con motivo/fuente.
Decisiones nuevas se registran primero en `docs/12-decisiones-tecnicas.md` o en
el documento canónico de dominio/deploy; aquí sólo enlace. Indicar proposed /
accepted / superseded cuando corresponda; nunca inferir aceptación del silencio.

Excluir logs, conversaciones, razonamiento paso a paso, diffs, listas de cambios,
resultados triviales, secretos, datos personales, estado diario, cantidades de
tests o disponibilidad temporal de un servidor. Eso pertenece a sus fuentes,
no se copia a la memoria.

## Compactación proporcional

- INDEX: objetivo máximo 25 líneas; sólo mapa.
- Notas de dominio: objetivo máximo 80 líneas por archivo; gotchas máximo 6 entradas.
- Revisar el archivo tocado al actualizar memoria; también cuando una fuente cambie.
- Al exceder el umbral: eliminar duplicados, actualizar/eliminar obsoleto, resumir
  y enlazar la fuente. Separar subdominio sólo si hay usos independientes reales.
- Mover detalles históricos útiles a `archive/` con fecha/fuente y enlace de
  sustitución; no cargar archivo histórico por defecto. Si Git/canónico ya
  conserva el detalle, no hacer otra copia sólo para llenar archive.
- Lead es responsable; un worker puede proponer compactación, no reescribir
  memoria ajena durante tareas paralelas. No hay barrido automático en cada sesión.
