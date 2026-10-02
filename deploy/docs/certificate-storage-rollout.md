# Despliegue de certificados — CERT-001

Implementación básica disponible en código, activación remota pendiente. Mantener
metadata/worker deshabilitado hasta desplegar un tag nuevo que incluya migración
`2026_10_02_000010`. No habilitar estas opciones con tags anteriores.

## Activación en testing

Después de publicar/revisar el commit y un tag único, actualizar el release en
inventory o usar `-e application_release_id=TAG_NUEVO` en todos los comandos.
Desde PC Uni, en `deploy/provisioning`, con wrappers disponibles:

```bash
ansible-playbook -i inventories/testing/hosts.yml playbooks/backup.yml -e backup_run_now=true
ansible-playbook -i inventories/testing/hosts.yml site.yml --tags redeploy --check --diff -e application_release_id=TAG_NUEVO -e application_storage_driver=pgsql -e queue_worker_enabled=true
ansible-playbook -i inventories/testing/hosts.yml site.yml --tags redeploy -e application_release_id=TAG_NUEVO -e application_storage_driver=pgsql -e queue_worker_enabled=true
```

Los dos flags deben activarse juntos. El deploy migra, actualiza permiso de descarga
para admin sin quitar permisos personalizados, reconstruye config, recarga FPM y
arranca/reinicia `medicina-certificates` tras activar el release/env.
Luego registrar ambos flags y release en inventory para futuras ejecuciones.
Purga siempre desactivada en este corte. No alterar Let's Encrypt.

## Validación en servidor

```bash
sudo systemctl status medicina-certificates --no-pager
sudo journalctl -u medicina-certificates -n 50 --no-pager
cd /var/www/medicina-laboral/current
php artisan queue:failed
php artisan schedule:list
```

Enviar PDF/JPG/PNG desde WhatsApp (máximo según política; defaults 3 × 5 MiB).
Esperar descarga y confirmar; descargar desde detalle admin. Probar usuario sin
permiso y archivo perteneciente a otro anticipo. Si falla la descarga de media,
infraestructura puede necesitar ampliar el proxy para el host de Meta retornado;
no ampliar a hosts arbitrarios. Revisar motivo seguro en registro/evento.
No compartir tokens, URLs temporales ni contenido médico en diagnósticos.

## Recuperación, límites y operación

Cola `database/certificates`, un worker; job 90 s, retry_after 120 s, descarga HTTP
30 s por request, 3 intentos con backoff 15/60 s. Pendientes >10 minutos se
republican en lotes de 100 cada 5 minutos; finalización idempotente por fila y locks.
Valores de descarga/recuperación en config; worker y retry_after deben conservar
margen coherente al cambiarlos. Purga no programada. Bytes no viajan en payloads
ni JSON; jobs sólo transportan ID técnico. Errores de provider/SQL se sanitizan.

Validación local: suite SQLite y script
`tests/Integration/certificate-postgres.php` en PostgreSQL desechable: migraciones,
bytea con bytes no UTF8, cola durable, confirmación idempotente, worker y cancelación
por segunda conexión durante descarga. El script exige DB aislada certificate_test;
no ejecutarlo en producción. Falta prueba real Meta/servicio systemd y rendimiento
bajo volumen institucional, PostgreSQL 17, backups/restore con binarios reales.
No declarar capacidad productiva a partir de estas pruebas.

Históricos metadata-only no adquieren contenido retroactivamente. No activar
purga ni revertir esta migración para hacer rollback de código: descartar tablas
perdería bytes y jobs. Mantener backup; para desactivar, parar worker y mantener
metadata conservada. La retención institucional se define en un corte posterior.
