# Backlog

## Objetivo

Este archivo concentra pendientes, mejoras, deudas técnicas, ideas y hallazgos detectados durante el desarrollo que no forman parte del milestone actual.

Su función es evitar que el agente o el equipo se desvíen del objetivo del día, sin perder visibilidad sobre temas relevantes encontrados en el camino.

---

## Reglas de uso

- Agregar aquí todo hallazgo que no entre en el milestone actual.
- No convertir este archivo en el plan del día.
- No mover tareas al plan diario sin decisión explícita.
- Mantener los items breves, claros y accionables.
- Si un item deja de tener sentido, marcarlo como resuelto o descartado.
- Si un item pasa a ser prioritario, promoverlo al `plan_dev/daily/YYYY-MM-DD.md` dejando referencia cruzada y fecha de promoción.
- Evitar duplicar aquí el mismo detalle que ya quedó consolidado en `plan_dev/STATUS.md`.

---

## Estados sugeridos

Usar uno de estos estados por item:

- `pending`
- `candidate`
- `blocked`
- `done`
- `discarded`

---

## Prioridades sugeridas

Usar una prioridad simple:

- `high`
- `medium`
- `low`

---

## Categorías sugeridas

Usar una categoría para agrupar mejor:

- `flujo`
- `arquitectura`
- `testing`
- `documentacion`
- `diagramas`
- `integracion`
- `scheduler`
- `mensajes`
- `modelo_datos`
- `devex`
- `operacion`

---

## Formato recomendado por item

```md
### [ID] Título corto
- estado:
- prioridad:
- categoría:
- detectado en:
- contexto:
- acción sugerida:
- dependencia:
- notas:
```

---

### [LOG-001] Definir política de datos sensibles en logs
- estado: `pending`
- prioridad: `high`
- categoría: `operacion`
- detectado en: `plan_dev/daily/2026-04-24.md`
- contexto: el sistema ya registra logs operativos y de debugging, incluyendo payloads o identificadores que pueden contener datos personales o sensibles.
- acción sugerida: definir política de redacción/minimización de PII, campos permitidos en logs, correlación segura, retención y criterios antes de ampliar el módulo administrativo.
- dependencia: definición de alcance del admin y criterios institucionales de protección de datos.
- notas: por decisión humana del 2026-04-24 no se implementa todavía, pero debe tratarse como pendiente importante. M6 del 2026-04-26 confirmó que existen logs con payloads completos en webhook/sender y que no debe aplicarse redacción parcial sin política explícita.

### [BO-001] Definir stack y matriz de permisos del backoffice
- estado: `pending`
- prioridad: `high`
- categoría: `arquitectura`
- detectado en: `plan_dev/daily/2026-04-26-02-backoffice.md`
- contexto: el backoffice requiere roles y permisos por módulo, acción y recurso. Todavía no está definido si se usará Spatie Laravel Permission o una implementación propia mínima.
- acción sugerida: confirmar stack de permisos y matriz inicial para `admin`, `auditor` y `director`, incluyendo si `director` tendrá solo lectura/reportes o acciones de estado.
- dependencia: antes de ejecutar `I2 - Auth, usuarios, roles y permisos`.
- notas: no se implementan permisos por campo o columna.

### [BO-002] Definir estrategia de storage privado de certificados
- estado: `pending`
- prioridad: `high`
- categoría: `operacion`
- detectado en: `plan_dev/daily/2026-04-26-02-backoffice.md`
- contexto: los certificados médicos no deben almacenarse ni servirse desde rutas públicas. El storage actual sigue metadata-only.
- acción sugerida: confirmar driver de storage privado y mecanismo de acceso: Temporary URLs o Controller Stream con validación de permisos.
- dependencia: antes de ejecutar `I4 - Storage privado de certificados`.
- notas: toda visualización o descarga debe auditarse.

### [BO-003] Definir operación manual de asociaciones aviso-certificado
- estado: `pending`
- prioridad: `medium`
- categoría: `modelo_datos`
- detectado en: `plan_dev/daily/2026-04-26-02-backoffice.md`
- contexto: la relación N a N `anticipo_certificado_aviso` ya existe, pero falta definir si el backoffice permitirá asociaciones adicionales manuales, automáticas o mixtas.
- acción sugerida: confirmar si se habilita asociar/desasociar desde UI y si desasociar borra pivot o cambia `estado_vinculo`.
- dependencia: antes de ejecutar `I7 - Asociación N a N en backoffice`.
- notas: no rediseñar la pivot existente.

### [BO-004] Definir futuro de `anticipos_certificado.aviso_id`
- estado: `pending`
- prioridad: `medium`
- categoría: `modelo_datos`
- detectado en: `plan_dev/daily/2026-04-26-02-backoffice.md`
- contexto: `anticipos_certificado.aviso_id` se conserva como compatibilidad temporal mientras la relación vigente usa la pivot `anticipo_certificado_aviso`.
- acción sugerida: confirmar si el campo queda como cache del primer aviso o si se depreca luego de migrar lecturas a la pivot.
- dependencia: antes de remover o reinterpretar lecturas legacy.
- notas: el backoffice nuevo debe leer asociaciones desde la relación N a N.
