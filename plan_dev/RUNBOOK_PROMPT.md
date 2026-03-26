# Runbook Prompt

## Objetivo

Este archivo define un prompt lanzador estándar para iniciar sesiones de trabajo de Codex sobre el proyecto.

Su función es:
- mantener un arranque consistente entre ejecuciones
- reducir variación innecesaria entre prompts diarios
- recordar la rutina documental obligatoria
- enfocar la ejecución en el primer milestone pendiente del día

No reemplaza la documentación operativa del repo.

---

## Prompt base recomendado

```md
Trabajá sobre este repo siguiendo estrictamente la rutina operativa definida en `AGENTS.md`.

Antes de hacer cambios:
- leé `AGENTS.md`
- leé `plan_dev/MASTER_PLAN.md`
- leé `plan_dev/STATUS.md`
- leé el plan diario correspondiente en `plan_dev/daily/YYYY-MM-DD.md` o el archivo diario que se indique

Reglas de ejecución:
- usá esos archivos como fuente de verdad actual
- ejecutá solo el primer milestone pendiente del plan diario
- no saltes milestones bloqueados salvo autorización explícita del plan diario
- si detectás ambigüedad funcional o técnica relevante, frená y dejá el milestone como `blocked` o `needs_review`
- si aparece trabajo fuera del alcance, registralo en `plan_dev/BACKLOG.md` sin desviar la ejecución
- al cerrar el milestone, actualizá `plan_dev/STATUS.md` con resultado, validaciones y próximo paso sugerido
- si el cambio impacta documentación, diagramas, arquitectura, flujos, testing o modelo, actualizá los documentos correspondientes

Formato esperado de respuesta:
1. análisis breve del estado actual para el milestone
2. plan corto de archivos a tocar
3. implementación
4. validaciones ejecutadas
5. actualización de estado y resumen final

Si falta alguno de los archivos obligatorios, dejalo explícito en el resumen final.
```

---

## Cómo usarlo

- copiar este prompt como base al abrir una nueva sesión
- completar `YYYY-MM-DD` por la fecha real o reemplazarlo por el archivo diario correspondiente
- agregar debajo el objetivo puntual del día si hace falta
- no duplicar en el prompt información que ya vive establemente en `AGENTS.md` o `plan_dev/`

---

## Cuándo ajustarlo

Modificar este archivo solo si cambia de verdad la rutina operativa del proyecto.

Si cambia una prioridad del día, el lugar correcto para eso es `plan_dev/daily/`, no este archivo.
