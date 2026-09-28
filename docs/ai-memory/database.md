# Base de datos

Revisado: 2026-09-27. Fuentes: [migraciones](../../database/migrations),
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
- Catálogos actuales en config, no tablas maestras implementadas; inventario y
  selección pendiente en [CAT-001](../15-inventario-tablas-maestras.md).
- DBML de [storage propuesto](../diagrams/db/certificate-storage-proposed.dbml)
  no es esquema actual. PostgreSQL bytea/cola son decisiones registradas para
  trabajo pendiente, no prueba de tablas implementadas; seguir
  [plan de certificados](../14-certificados-problematica-y-plan-incremental.md).

Un owner para migraciones relacionadas y orden de aplicación. Acordar nombres,
FKs, nullabilidad, backfill y compatibilidad antes de dividir tests/runtime.
`tests/Concerns/CreatesTestingSchema.php` crea esquema manual de pruebas: revisar
si debe acompañar la migración, sin asumir que cubre ejecutarla en PostgreSQL.
