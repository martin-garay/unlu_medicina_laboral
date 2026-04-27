# Status

## Objetivo

Este archivo consolida el estado actual del proyecto y deja trazada la última ejecución relevante.

No debe reemplazar:
- el roadmap de `plan_dev/MASTER_PLAN.md`
- el detalle operativo de `plan_dev/daily/`
- el backlog de `plan_dev/BACKLOG.md`

---

## Fecha de última actualización
2026-04-27 14:59 -03

## Resumen ejecutivo
- Estado general del proyecto: se esta ejecutando la segunda daily del 2026-04-26 para preparar el backoffice con Filament antes de implementar runtime.
- Último bloque completado: `BO-1` del daily `2026-04-26-02-backoffice`, creando la documentacion arquitectonica base del backoffice.
- Milestone actual: próximo milestone pendiente `BO-2 - Crear plan de implementacion incremental del backoffice`.
- Próximo paso sugerido: ejecutar BO-2 para ordenar milestones, tests y criterios de aceptacion antes de instalar Filament o crear Resources.

---

## Estado global

### Documentación
- estado: `in_progress`
- notas: la estructura operativa nueva ya tiene roles, precedencia y prompt lanzador estándar. M5.4 sincronizó documentos de modelo, flujo, validaciones y decisiones técnicas para reflejar estado `inicial`, adjuntos múltiples y relación N a N antes de M6.

### Motor de conversación
- estado: `in_progress`
- notas: el motor ya tiene una capa común de interacción (`ConversationInteractionService`), lookup/alta por canal y una entrada interna de prueba sin depender de WhatsApp. Desde M2, toda conversación nueva responde bienvenida + menú y no interpreta el primer mensaje como selección de flujo.

### Flujos
- aviso: `in_progress`
- anticipo: `in_progress`
- notas: la decisión vigente es tomar `AnticipoCertificado` como entidad actual de certificado y no crear una entidad nueva. RF-03.1 quedó cubierto en M5.2: el flujo permite adjuntar hasta `medicina_laboral.certificados.max_files` archivos/imágenes antes de confirmar.

### Modelo de datos
- estado: `in_progress`
- notas: M5.1 implementó `avisos.estado` con default `inicial`, cambio de default de `anticipos_certificado.estado` a `inicial` y migración de anticipos existentes con estado `registrado`. M5.3 agregó la pivot `anticipo_certificado_aviso` para relación N a N, con backfill desde `anticipos_certificado.aviso_id` y compatibilidad temporal con la relación legacy.

### Testing
- estado: `in_progress`
- notas: M5.3 ejecutó `make test`: `124 passed`, `420 assertions`.

### Inactividad / scheduler
- estado: `in_progress`
- notas: el scheduler de timeouts existe y no fue modificado en este milestone.

### Logs / operación
- estado: `in_progress`
- notas: M6 relevó logs actuales y clasificó estructura objetivo en debug, operación, auditoría y métricas. La política de datos sensibles en logs queda como pendiente importante en `plan_dev/BACKLOG.md` (`LOG-001`), por decisión humana no se implementa todavía.

### Admin / roles / permisos
- estado: `in_progress`
- notas: BO-1 documento la arquitectura base Laravel + Filament, separacion por capas, seguridad/auditoria y storage privado esperado. Aun no hay implementacion runtime de Filament, auth administrativa ni permisos.

### Integraciones futuras
- estado: `pending`
- notas: siguen planteadas como desacopladas y futuras.

### Deploy / Ansible
- estado: `pending`
- notas: no existe todavía base Ansible/Vagrant; el daily 2026-04-24 lo trata como frente separado.

---

## Última ejecución del agente

### Fecha/hora
- 2026-04-27 14:59 -03

### Plan diario usado
- `plan_dev/daily/2026-04-26-02-backoffice.md`

### Milestone trabajado
- `BO-1 - Crear documentacion arquitectonica base del backoffice`

### Resultado
- `done`

### Resumen corto
- se creo la documentacion arquitectonica base del backoffice con Filament, dejando fijada la separacion entre UI administrativa, Application Actions, Domain Services, modelos, seguridad, auditoria y storage de archivos medicos.

---

## Cambios realizados
- archivos tocados: `docs/README.md`, `docs/backoffice/README.md`, `docs/backoffice/architecture.md`, `docs/backoffice/domain-separation.md`, `docs/backoffice/filament-guidelines.md`, `docs/backoffice/security-and-audit.md`, `docs/backoffice/storage-and-sensitive-files.md`, `plan_dev/daily/2026-04-26-02-backoffice.md` y `plan_dev/STATUS.md`
- resumen técnico: la documentacion toma como base que `AnticipoCertificado` es la entidad vigente de certificado, que la relacion N a N existe mediante `anticipo_certificado_aviso`, y que Filament debe delegar operaciones de negocio en Application Actions reutilizables.
- documentación actualizada: sí, arquitectura base de backoffice y estado consolidado
- diagramas actualizados: no aplica en BO-1

---

## Validaciones

### Automáticas
- tests corridos: no aplica, milestone documental/analítico sin cambios runtime
- resultado: no aplica
- otros checks: `git diff --check`
- resultado: sin errores de whitespace

### Manuales sugeridas
- revisar que la documentacion de `docs/backoffice/` sea suficiente para iniciar el plan incremental de BO-2
- revisar que la estrategia de seguridad de archivos medicos quede como requisito base antes de implementar Filament

---

## Bloqueos actuales
- falta definir matriz mínima de permisos para `auditor` y `director`

---

## Decisiones humanas pendientes
- definir matriz mínima de permisos para `auditor` y `director`
- confirmar si avisos `observado` deben poder recibir anticipo o si pasan a ser bloqueantes
- confirmar si futuras asociaciones adicionales de avisos serán manuales, automáticas o mixtas
- confirmar si `anticipos_certificado.aviso_id` quedará como cache del primer aviso o se eliminará luego de migrar lecturas a pivot
- confirmar stack de permisos para backoffice: Spatie Laravel Permission o implementación propia mínima
- confirmar estrategia de storage privado para certificados médicos

---

## Próximo milestone recomendado
- ejecutar `BO-2` de `plan_dev/daily/2026-04-26-02-backoffice.md`: crear plan de implementacion incremental del backoffice

---

## Referencia breve a backlog
- `LOG-001`: definir política de datos sensibles en logs quedó registrado como pendiente importante.
