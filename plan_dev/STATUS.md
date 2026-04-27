# Status

## Objetivo

Este archivo consolida el estado actual del proyecto y deja trazada la última ejecución relevante.

No debe reemplazar:
- el roadmap de `plan_dev/MASTER_PLAN.md`
- el detalle operativo de `plan_dev/daily/`
- el backlog de `plan_dev/BACKLOG.md`

---

## Fecha de última actualización
2026-04-27 16:00 -03

## Resumen ejecutivo
- Estado general del proyecto: la base tecnica del backoffice con Filament quedo instalada y validada.
- Último bloque completado: `I1 - Base tecnica de Filament` del daily `2026-04-26-02-backoffice`.
- Milestone actual: listo para preparar `I2 - Auth, usuarios, roles y permisos`.
- Próximo paso sugerido: resolver `BO-001` antes de implementar `I2`, definiendo stack y matriz minima de permisos para `admin`, `auditor` y `director`.

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
- notas: I1 instalo Filament `v5.6.1`, agrego panel base en `/admin`, auth minima con `App\Models\User` y restriccion por `is_admin`. Roles y permisos granulares siguen pendientes para I2 y dependen de `BO-001`.

### Integraciones futuras
- estado: `pending`
- notas: siguen planteadas como desacopladas y futuras.

### Deploy / Ansible
- estado: `pending`
- notas: no existe todavía base Ansible/Vagrant; el daily 2026-04-24 lo trata como frente separado.

---

## Última ejecución del agente

### Fecha/hora
- 2026-04-27 16:00 -03

### Plan diario usado
- `plan_dev/daily/2026-04-26-02-backoffice.md`

### Milestone trabajado
- `I1 - Base tecnica de Filament`

### Resultado
- `done`

### Resumen corto
- se instalo y configuro la base tecnica de Filament, se agrego auth administrativa minima y el panel quedo accesible en `/admin` sin exponer contenido administrativo a usuarios no autenticados.

---

## Cambios realizados
- archivos tocados: `composer.json`, `composer.lock`, `docker-compose.yml`, `docker/app/Dockerfile`, `README.md`, `config/app.php`, `config/auth.php`, `app/Providers/Filament/AdminPanelProvider.php`, `app/Models/User.php`, `database/migrations/2026_04_27_000008_create_users_table.php`, `app/Application/.gitkeep`, `app/Domain/.gitkeep`, assets publicados de Filament, `tests/Feature/Backoffice/AdminPanelAccessTest.php`, `docs/backoffice/implementation-plan.md`, `plan_dev/daily/2026-04-26-02-backoffice.md` y `plan_dev/STATUS.md`
- resumen técnico: Docker ahora permite usar Composer y Laravel con `ext-intl`; Filament quedo instalado; el panel admin se registro en `/admin`; se agrego usuario autenticable minimo con flag `is_admin` y tests de acceso basico.
- documentación actualizada: sí, plan incremental, daily y estado consolidado
- diagramas actualizados: no aplica en I1

---

## Validaciones

### Automáticas
- tests corridos: `make test`
- resultado: `127 passed`, `425 assertions`
- otros checks: `git diff --check`, `make migrate`, `php artisan route:list --path=admin`, `php artisan about`
- resultado: sin errores

### Manuales sugeridas
- abrir `/admin/login` en el navegador local y verificar render visual del login de Filament.
- definir `BO-001` antes de avanzar con roles y permisos granulares.

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
- resolver `BO-001` y luego iniciar `I2 - Auth, usuarios, roles y permisos` segun `docs/backoffice/implementation-plan.md`

---

## Referencia breve a backlog
- `LOG-001`: definir política de datos sensibles en logs quedó registrado como pendiente importante.
- `BO-001`: definir stack y matriz de permisos del backoffice.
- `BO-002`: definir estrategia de storage privado de certificados.
- `BO-003`: definir operación manual de asociaciones aviso-certificado.
- `BO-004`: definir futuro de `anticipos_certificado.aviso_id`.
