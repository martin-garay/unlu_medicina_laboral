# Worker de certificados

Debian 13/systemd/PHP 8.4. Un proceso, cola database/certificates en PostgreSQL.
Opt-in `queue_worker_enabled`; restart después de release/env nuevo, configuración
sin secretos. Job timeout 90 s < retry_after 120 s; descarga HTTP 30 s.
Service `medicina-certificates`; logs en journal y Laravel. `max-time` recicla
el proceso. Purga desactivada. No declarar operación sin prueba PostgreSQL/Meta.
Validación local: syntax/lint; remota: `systemctl status medicina-certificates`,
`queue:failed`, envío real y aislamiento de intentos.
