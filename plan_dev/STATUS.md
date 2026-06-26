# Status

## Objetivo

Este archivo consolida el estado actual del proyecto y deja trazada la última ejecución relevante.

No debe reemplazar:
- el roadmap de `plan_dev/MASTER_PLAN.md`
- el detalle operativo de `plan_dev/daily/`
- el backlog de `plan_dev/BACKLOG.md`

---

## Fecha de última actualización
2026-06-26 08:37 -03

## Resumen ejecutivo
- Estado general del proyecto: el motor conversacional sigue en progreso y ya soporta menus interactivos por paso para selecciones acotadas de WhatsApp, manteniendo fallback por texto/numero.
- Último bloque completado: `M4 - Cierre documental y operativo`.
- Milestone actual: daily 2026-06-26 cerrado.
- Próximo paso sugerido: probar manualmente los menus por WhatsApp real y repetir un webhook con el mismo `wamid` para confirmar que no hay segunda respuesta.

---

## Estado global

### Documentación
- estado: `in_progress`
- notas: la estructura operativa nueva ya tiene roles, precedencia y prompt lanzador estándar. La documentación de backoffice ya registra I1, I2 base, I3 base, especificación fina de módulos administrativos y convención de botón `Volver` en pantallas de detalle en `docs/backoffice/module-specs.md`. Quedaron creadas dailies separadas para módulos read-only, usuarios/roles, dashboard/reportes y storage/configuración.

### Motor de conversación
- estado: `in_progress`
- notas: el motor ya tiene una capa común de interacción (`ConversationInteractionService`), lookup/alta por canal y una entrada interna de prueba sin depender de WhatsApp. Desde M2 del daily 2026-06-26, `StepResult` puede devolver `menuConfig` y el motor emite menus interactivos especificos de paso ademas del menu principal. Desde M3, los mensajes entrantes duplicados con el mismo `provider_message_id` se ignoran antes de ejecutar handlers para evitar avances fantasma.

### Flujos
- aviso: `in_progress`
- anticipo: `in_progress`
- notas: la decisión vigente es tomar `AnticipoCertificado` como entidad actual de certificado y no crear una entidad nueva. Los pasos de sede, jornada laboral, tipo de ausentismo, domicilio circunstancial, tipo de certificado y adjuntar otro archivo usan menus interactivos en WhatsApp y conservan compatibilidad por texto/numero.

### Modelo de datos
- estado: `in_progress`
- notas: M5.1 implementó `avisos.estado` con default `inicial`, cambio de default de `anticipos_certificado.estado` a `inicial` y migración de anticipos existentes con estado `registrado`. M5.3 agregó la pivot `anticipo_certificado_aviso` para relación N a N, con backfill desde `anticipos_certificado.aviso_id` y compatibilidad temporal con la relación legacy.

### Testing
- estado: `in_progress`
- notas: última corrida completa `make test`: `209 passed`, `869 assertions`.

### Inactividad / scheduler
- estado: `in_progress`
- notas: el scheduler de timeouts existe y no fue modificado en este milestone.

### Logs / operación
- estado: `in_progress`
- notas: M6 relevó logs actuales y clasificó estructura objetivo en debug, operación, auditoría y métricas. La política de datos sensibles en logs queda como pendiente importante en `plan_dev/BACKLOG.md` (`LOG-001`), por decisión humana no se implementa todavía.

### Admin / roles / permisos
- estado: `base_done`
- notas: I1 instalo Filament `v5.6.1`, agrego panel base en `/admin` y auth minima con `App\Models\User`. I2 base quedo cerrado: Spatie Laravel Permission `6.25.0`, matriz en `config/backoffice.php`, seeder idempotente de roles/permisos/admin local y acceso al panel por `backoffice.access`. En desarrollo, `admin` debe sincronizar todos los permisos definidos; eso no saltea restricciones read-only de cada Resource. Desde P2 existe `ConversacionResource` read-only protegido por `conversaciones.view`. Desde P3 existe permiso `conversaciones.historial.view`, acción de ojo y pantalla de historial read-only. En la daily read-only P2/P3 se agrego `AvisoResource` con listado y detalle read-only. En P4/P5 se agrego `AnticipoCertificadoResource` con listado y detalle read-only, sin descarga, preview ni exposicion de `storage_path`. En P6 se agrego `AuditoriaAdministrativaResource` read-only. En la daily de usuarios P1 se agrego `UserResource` read-only. Desde 2026-05-04 todas las pantallas de detalle/historial abiertas desde listados tienen accion `Volver`.

### Auditoria administrativa
- estado: `base_done`
- notas: I3 base quedo cerrado. Existe contrato documental, tabla `auditoria_administrativa`, modelo `App\Models\AuditoriaAdministrativa`, servicio `App\Domain\Auditoria\Services\AuditoriaAdministrativaService`, tests y una integracion inicial en el seeder de roles/permisos con eventos `permissions.seeded` y `roles.seeded`. Desde P6 existe Resource read-only con listado, filtros y detalle.

### Integraciones futuras
- estado: `pending`
- notas: siguen planteadas como desacopladas y futuras.

### Deploy / Ansible
- estado: `pending`
- notas: no existe todavía base Ansible/Vagrant; el daily 2026-04-24 lo trata como frente separado.

---

## Última ejecución del agente

### Fecha/hora
- 2026-06-26 08:37 -03

### Plan diario usado
- `plan_dev/daily/2026-06-26.md`

### Milestone trabajado
- `M4 - Cierre documental y operativo`

### Resultado
- `done`

### Resumen corto
- se cerro el daily con M2 y M3 implementados, validados y commiteados; queda pendiente solo validacion manual por WhatsApp real.

---

## Cambios realizados
- archivos tocados: `plan_dev/daily/2026-06-26.md` y `plan_dev/STATUS.md`
- resumen técnico: cierre operativo del daily; no hubo cambios runtime en M4.
- documentación actualizada: sí, daily y estado consolidado
- diagramas actualizados: no; no cambio el grafo de estados/transiciones

---

## Validaciones

### Automáticas
- tests corridos: `make test`
- resultado: `211 passed`, `879 assertions`
- otros checks: `git diff --check`
- resultado: sin errores

### Manuales sugeridas
- probar por WhatsApp real los menus de sede, jornada laboral, tipo de ausentismo, domicilio circunstancial, tipo de certificado y adjuntar otro archivo.
- reenviar desde ngrok o repetir manualmente un webhook con el mismo `wamid` y verificar que no haya segunda respuesta ni cambio de paso.
- revisar en logs el evento operativo `Duplicate inbound message ignored`.

---

## Bloqueos actuales
- no hay bloqueos activos para este daily.

---

## Decisiones humanas pendientes
- confirmar si avisos `observado` deben poder recibir anticipo o si pasan a ser bloqueantes
- confirmar si futuras asociaciones adicionales de avisos serán manuales, automáticas o mixtas
- confirmar si `anticipos_certificado.aviso_id` quedará como cache del primer aviso o se eliminará luego de migrar lecturas a pivot
- confirmar estrategia de storage privado para certificados médicos

---

## Próximo milestone recomendado
- validar manualmente en WhatsApp real y definir el próximo daily.

---

## Referencia breve a backlog
- `LOG-001`: definir política de datos sensibles en logs quedó registrado como pendiente importante.
- `BO-001`: definido y marcado `done`; matriz en `docs/backoffice/permissions.md`.
- `BO-002`: definir estrategia de storage privado de certificados.
- `BO-003`: definir operación manual de asociaciones aviso-certificado.
- `BO-004`: definir futuro de `anticipos_certificado.aviso_id`.
