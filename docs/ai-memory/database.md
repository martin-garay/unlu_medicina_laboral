# Base de datos

Revisado: 2026-09-30. Fuentes: [migraciones](../../database/migrations),
[modelos](../../app/Models), [modelo documentado](../04-modelo-de-datos.md).

- PostgreSQL es runtime; tests base usan SQLite en memoria. Un test SQLite no
  valida índices, locks, SQL o tipos específicos PostgreSQL.
- Conversaciones/mensajes/eventos separados de avisos/anticipos/archivos.
  No borrar trazabilidad al cancelar ni materializar negocio antes de confirmar.
- Pivot `anticipo_certificado_aviso` relaciona N a N. `aviso_id` legacy aún existe;
  no retirarlo como limpieza incidental. Ver decisión de relación en
  [registro técnico](../12-decisiones-tecnicas.md).
- `metadata.identificacion`, `metadata.aviso` y `metadata.certificado` guardan
  borrador/instantáneas. Añadir FK a catálogo no autoriza reemplazar históricos.
- Catálogos conversacionales activos en PostgreSQL: `sedes`, `tipos_ausentismo`,
  `tipos_certificado`, `parentescos`; IDs internos y códigos estables, baja lógica.
  `jornada_laboral` permanece libre. Ver selección residual en el
  [inventario CAT-001](../15-inventario-tablas-maestras.md).
- CERT-001 agrega contenido `bytea` 1:1, intento UUID, estado técnico, jobs y
  failed_jobs; el FK anticipo del archivo es nullable hasta confirmar. Ver DBML
  runtime y [rollout](../../deploy/docs/certificate-storage-rollout.md). Purga desactivada.

Un owner para migraciones relacionadas y orden de aplicación. Acordar nombres,
FKs, nullabilidad, backfill y compatibilidad antes de dividir tests/runtime.
`tests/Concerns/CreatesTestingSchema.php` crea esquema manual de pruebas: revisar
si debe acompañar la migración, sin asumir que cubre ejecutarla en PostgreSQL.
