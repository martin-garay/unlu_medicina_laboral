# Despliegue de almacenamiento privado de certificados

Estado: plan pendiente de implementación y validación. Ejecución ordenada por
M5–M9 de `plan_dev/daily/2026-09-07.md`, después del cierre del deploy M4.

## Decisión vigente — 2026-09-14

PostgreSQL con contenido `bytea` en tabla separada reemplaza el destino local
privado propuesto anteriormente. No está implementado. Los criterios funcionales
viven en [storage](../../docs/backoffice/storage-and-sensitive-files.md).

## Preparación

- Resolver BO-002 antes de habilitar el nuevo driver; retención todavía pendiente; D1 aprobó descarga mediante cola PostgreSQL.
- Agregar migración y drivers de base de datos manteniendo metadata-only como
  compatibilidad explícita. No presentar registros históricos como archivos disponibles.
- Seleccionar drivers y límites desde config/inventory; no activar nombres de
  drivers que todavía no existen. Mantener secretos en Vault y fuera de logs/diff.
- Validar DNS, TLS, proxy y acceso a API y media desde el runtime destino.
- D1 aprobó cola PostgreSQL: implementar worker administrado, reinicio por release,
  límites de concurrencia y monitoreo antes del pase. Validar migraciones de jobs,
  publicación durable, recuperación tras caída y visibilidad de trabajos fallidos.

## Capacidad, respaldo y continuidad

- Medir tamaño total de la tabla de binarios incluyendo TOAST e índices,
  crecimiento diario, espacio libre, WAL generado y acumulado, conexiones e I/O.
- Definir demanda esperada y objetivos medibles de latencia, ventana de backup,
  RPO/RTO y margen de disco antes del pase a producción. No hay un umbral universal
  de cantidad de archivos que obligue a cambiar de backend.
- Probar con datos sintéticos a volumen previsto y concurrencia de cargas,
  descargas y listados; comparar p95 y memoria PHP con la línea base sin descargas.
- Verificar que consultas de metadata no lean contenido y revisar sus planes.
- Limitar concurrencia según mediciones. No suponer que streaming HTTP evita
  cargar el `bytea` completo en PDO/PHP.
- El backup consistente de PostgreSQL debe incluir tabla binaria, TOAST y metadata;
  medir duración/tamaño y restaurar en destino aislado verificando hash y asociación.
- El backup de base no sustituye respaldo de configuración u otros datos del sistema.
- Revisar arquitectura si el crecimiento proyectado agota el margen de disco,
  backup/restore excede los objetivos o las descargas degradan el p95 acordado.
  Evaluar almacenamiento externo antes de alcanzar esos límites, con migración
  por lotes, verificación de hashes y compatibilidad de lectura.
- Rollback de código no debe borrar la tabla ni bytes. Verificar compatibilidad
  del release anterior con referencias y drivers; si no puede leerlos, definir
  cómo detener cargas y recuperar servicio sin perder los datos nuevos.
- No habilitar purga automática sin política acordada.

## Validación y promoción

1. Ejecutar `bin/check-deploy` desde `deploy/provisioning`.
2. En Vagrant, validar convergencia, segunda ejecución idempotente, permisos reales,
   privacidad HTTP, persistencia entre releases, backup/restore y rollback.
3. Cerrar M4 y preparar un tag nuevo desde el commit validado, sin modificar
   `testing-2026-09-07-01` ni tags anteriores. Registrar commit y release seleccionados.
4. Desde PC Uni seguir `deployment-guide.md`: check/diff sin secretos, apply,
   health checks con curl y monitoreo; mantener `security_enabled: false` y
   `firewall_enabled: false` en testing según su decisión operativa vigente.
5. Probar WhatsApp con PDF/foto sintéticos desde menú 3 hasta anticipo registrado;
   verificar binario, hash, asociaciones y errores sin falso éxito.
6. Ejecutar segunda pasada idempotente y registrar resultados en
   `validation-matrix.md`, incluyendo release, entorno, pruebas y pendientes.

No promover a producción dentro de este corte. Si falta acceso, conectividad,
credencial o aceptación real, registrar el bloqueo sin afirmar disponibilidad.

## Criterios de aceptación

- Binario durable en PostgreSQL y asociado correctamente al anticipo confirmado.
- Sin acceso público, pérdida entre releases ni secretos en evidencia operativa.
- Backup recuperable con hash comprobado y rollback compatible ensayado.
- `/up`, `/internal/chat`, aviso de ausencia y scheduler sin regresiones.
- Evidencia de WhatsApp real; tests simulados no reemplazan esta aceptación.

## Limpieza de borradores — operación propuesta

- Reutilizar el cron existente de `schedule:run`; agregar la tarea en Laravel,
  sin SQL de limpieza en cron. Frecuencia, gracia, lotes y presupuesto temporal
  deben provenir de config/inventory según el
  [diseño de ciclo de vida](../../docs/backoffice/certificate-attachment-lifecycle.md).
- Primero ejecutar `--dry-run`, después habilitar por entorno con datos sintéticos;
  no hay purga implementada ni habilitada por este documento.
- Registrar última ejecución exitosa, candidatos, purgados, bytes lógicos según
  metadata, fallos y atraso del candidato elegible más antiguo, sin binarios.
- Monitorear autovacuum de tabla y TOAST. Borrar contenido habilita reutilización
  de espacio tras vacuum; no garantiza que disminuya inmediatamente el archivo
  de base en el sistema operativo. No programar `VACUUM FULL` como limpieza diaria.
  Referencia: [PostgreSQL VACUUM](https://www.postgresql.org/docs/16/sql-vacuum.html).
- El presupuesto de ejecución debe preservar la cadencia de inactividad existente;
  ajustar timeout SQL/locks y medirlo antes de aumentar los lotes.
- Un restore puede recuperar borradores purgados después del backup. Revisar
  elegibilidad antes de reactivar tareas; no confundir purga activa con eliminación
  de todas las copias históricas.

## Contrato de settings para M8

La [matriz de M5](../../docs/backoffice/certificate-storage-settings.md) debe
mapearse a inventory/defaults/templates sin duplicar límites de runtime. Documentar
qué cambios requieren regenerar config cache y reiniciar workers; verificar que
web, worker y scheduler reciben la misma versión efectiva. Umbrales de capacidad,
alertas, concurrencia y limpieza se configuran por entorno con rangos validados.
No habilitar capacidades con combinaciones incompatibles de límites y timeouts.
