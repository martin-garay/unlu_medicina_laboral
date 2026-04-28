# Status

## Objetivo

Este archivo consolida el estado actual del proyecto y deja trazada la última ejecución relevante.

No debe reemplazar:
- el roadmap de `plan_dev/MASTER_PLAN.md`
- el detalle operativo de `plan_dev/daily/`
- el backlog de `plan_dev/BACKLOG.md`

---

## Fecha de última actualización
2026-04-28 09:30 -03

## Resumen ejecutivo
- Estado general del proyecto: la base tecnica del backoffice con Filament quedo instalada y validada.
- Último bloque completado: apertura del daily ejecutable `2026-04-28` para resolver `BO-001` y avanzar con `I2 - Auth, usuarios, roles y permisos`.
- Milestone actual: `D1 - Resolver BO-001: stack y matriz inicial de permisos`.
- Próximo paso sugerido: ejecutar `D1` del daily `plan_dev/daily/2026-04-28.md`.

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
- notas: I1 ejecuto `make test`: `127 passed`, `425 assertions`, incluyendo cobertura basica de acceso al panel Filament.

### Inactividad / scheduler
- estado: `in_progress`
- notas: el scheduler de timeouts existe y no fue modificado en este milestone.

### Logs / operación
- estado: `in_progress`
- notas: M6 relevó logs actuales y clasificó estructura objetivo en debug, operación, auditoría y métricas. La política de datos sensibles en logs queda como pendiente importante en `plan_dev/BACKLOG.md` (`LOG-001`), por decisión humana no se implementa todavía.

### Admin / roles / permisos
- estado: `in_progress`
- notas: I1 instalo Filament `v5.6.1`, agrego panel base en `/admin`, auth minima con `App\Models\User` y restriccion por `is_admin`. El daily 2026-04-28 propone Spatie Laravel Permission, roles `admin`, `auditor`, `director` y permisos base para desbloquear I2.

### Integraciones futuras
- estado: `pending`
- notas: siguen planteadas como desacopladas y futuras.

### Deploy / Ansible
- estado: `pending`
- notas: no existe todavía base Ansible/Vagrant; el daily 2026-04-24 lo trata como frente separado.

---

## Última ejecución del agente

### Fecha/hora
- 2026-04-28 09:30 -03

### Plan diario usado
- `plan_dev/daily/2026-04-28.md`

### Milestone trabajado
- `D0 - Abrir daily ejecutable de backoffice para I2`

### Resultado
- `done`

### Resumen corto
- se creo el daily 2026-04-28 para ejecutar `BO-001` e `I2`, dejando `D1` como primer milestone pendiente.

---

## Cambios realizados
- archivos tocados: `README.md`, `plan_dev/daily/2026-04-28.md` y `plan_dev/STATUS.md`
- resumen técnico: se documento el usuario local de prueba en README y se planifico la ejecucion de permisos del backoffice.
- documentación actualizada: sí, README operativo, daily y estado consolidado
- diagramas actualizados: no aplica

---

## Validaciones

### Automáticas
- tests corridos: no aplica, cambio documental/planificacion
- resultado: no aplica
- otros checks: `git diff --check`
- resultado: sin errores

### Manuales sugeridas
- revisar la matriz propuesta en `plan_dev/daily/2026-04-28.md`.
- ejecutar `D1` antes de instalar dependencias o tocar runtime.

---

## Bloqueos actuales
- `I2` depende de `BO-001`
- `I4` depende de `BO-002`
- `I7` depende de `BO-003`

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
- ejecutar `D1 - Resolver BO-001: stack y matriz inicial de permisos` del daily `plan_dev/daily/2026-04-28.md`

---

## Referencia breve a backlog
- `LOG-001`: definir política de datos sensibles en logs quedó registrado como pendiente importante.
- `BO-001`: definir stack y matriz de permisos del backoffice.
- `BO-002`: definir estrategia de storage privado de certificados.
- `BO-003`: definir operación manual de asociaciones aviso-certificado.
- `BO-004`: definir futuro de `anticipos_certificado.aviso_id`.
