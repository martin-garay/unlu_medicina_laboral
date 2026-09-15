# Adjuntos: límites, estados y limpieza

Estado: estrategia propuesta en M5 el 2026-09-14; sin implementación ni purga activa.
Backend aprobado: PostgreSQL `bytea` en tabla separada. Los plazos operativos que
siguen son propuestas configurables, no políticas institucionales aprobadas.

## Política única de límites

Usar `medicina_laboral.certificados.max_files`, `max_size_kb`,
`allowed_mime_types` y `allowed_extensions` como fuente de verdad en config.
`max_size_kb` se interpreta en KiB (1024 bytes); se conserva la clave por
compatibilidad. Exponer los límites operativos por variables de entorno en config.
No duplicar defaults en handlers, storage, mensajes, migraciones o tests funcionales.

Un servicio de política de adjuntos debe entregar límites efectivos, validar
configuración y producir los parámetros de mensajes. Textos en `lang/es`, con
placeholders de tamaño, cantidad y formatos. El mismo tamaño efectivo anunciado
se usa para rechazar bytes, tanto al ingreso si hay metadata como durante la
descarga real. No confiar en Content-Length ni MIME provisto por Meta.

Para evitar que un cambio de configuración contradiga lo anunciado, tomar una
instantánea de la política al iniciar cada intento de carga, sin bytes, y usarla
en mensajes y validaciones hasta su cierre. Nuevos intentos toman la nueva config.
Un descenso urgente que requiera invalidar intentos previos debe cerrarlos e
informar el cambio, no cambiarles el límite silenciosamente. La infraestructura
debe admitir las políticas de intentos todavía activos.

Hallazgo actual: `max_size_kb` existe pero el handler de adjuntos no verifica
`size_bytes` ni bytes reales; el mensaje de adjuntar no informa el tamaño y tiene
formatos literales. Corregir ambos en el corte funcional de M6, con tests de config
alternativa y límites exactos (máximo y máximo + 1 byte). No anunciar validación
implementada mientras el runtime siga metadata-only.

## Modelo futuro: registrar una vez y asociar al confirmar

Propuesta: crear `anticipo_certificado_archivos` desde el borrador con
`conversacion_id`, identificador del intento de carga, estado técnico y metadata.
Hacer nullable `anticipo_certificado_id` hasta confirmar. El registro es técnico;
no se crea `AnticipoCertificado` antes de la confirmación del usuario.

Guardar el contenido en `anticipo_certificado_archivo_contenidos`, con
`archivo_id` como PK/FK única y `contenido bytea`. Su existencia significa que
hay bytes; los registros históricos metadata-only no se consideran disponibles.

[Esquema propuesto, no aplicado](../diagrams/db/certificate-storage-proposed.dbml).
El esquema de runtime se actualizará junto con las migraciones, no antes.

| Estado técnico | Contenido | Uso |
| --- | --- | --- |
| pendiente | ausente | Recepción registrada; descarga pendiente o en curso. |
| disponible | presente | Descargado y validado; todavía borrador. |
| confirmado | presente | Asociado al anticipo, fuera de limpieza de borradores. |
| fallido | ausente | Descarga o validación fallida; motivo registrado. |
| descartado | puede existir | Intento cancelado o vencido; no confirmable. |
| purgado | ausente | Contenido eliminado; metadata y eventos conservados. |

El estado técnico es distinto del estado de negocio del anticipo y de
`estado_validacion`. Marcar timestamps y motivo de descarte/purga.

Confirmación, en una transacción corta:

1. Bloquear conversación y registros técnicos en orden consistente.
2. Validar intento vigente, propiedad, estado disponible y existencia del contenido.
3. Crear anticipo y asociaciones; actualizar FK y estado a confirmado.
4. Conservar los IDs y la fila `bytea`, sin leer/copiar/reinsertar el contenido.

Si falla, la transacción revierte. Repetir una confirmación devuelve el resultado
ya creado; no duplica filas ni contenido. En la migración adaptar listados,
policies y reportes para que registros borradores no aparezcan como certificados
confirmados. Mantener compatibilidad explícita de históricos metadata-only.

Deduplicar reentregas por mensaje del proveedor e intento, mediante restricción
única y persistencia idempotente; no deduplicar documentos de personas diferentes
por hash. La finalización de una descarga verifica bajo lock que el intento
siga vigente antes de insertar bytes. Un worker tardío no revive un descartado.

## Vencimiento y limpieza mediante Laravel Scheduler

Reutilizar el Scheduler existente, con comando Artisan y servicio de limpieza.
El cron del servidor solo dispara `schedule:run`; no contiene SQL ni lógica de purga.
Operación detallada en [despliegue](../../deploy/docs/certificate-storage-rollout.md).

La expiración funcional y la eliminación física son momentos distintos:

- Cuando vence o se cancela la conversación, descartar sus borradores pendientes,
  disponibles o fallidos, registrando `descartado_en` y `purgar_despues_de`.
- Cancelar/reiniciar el subflujo o volver al menú también descarta el intento,
  aunque la conversación permanezca activa. Usar ID de intento para no confundir
  archivos previos con un nuevo anticipo dentro de la misma conversación.
- El vencimiento proviene de las reglas conversacionales existentes, no de un
  TTL independiente desde la subida que pueda borrar un trámite activo.
- Una revisión periódica reconcilia cierres cuyo evento no terminó de procesarse.
- No descartar por antigüedad solamente un intento con procesamiento vigente;
  definir recuperación de workers/leases al resolver la modalidad de descarga.

Valores iniciales propuestos bajo `medicina_laboral.certificados.cleanup`:

| Parámetro propuesto | Default propuesto | Significado |
| --- | --- | --- |
| enabled | false hasta validación | Habilitación explícita por entorno. |
| grace_hours | 24 | Conservación desde descarte antes de eliminar bytes. |
| cron | cada hora | Frecuencia de selección de vencidos. |
| batch_size | 100 | Máximo de candidatos por lote. |
| max_run_seconds | 20 | Presupuesto de trabajo por ejecución. |
| lock_minutes | configurable y mayor al tiempo máximo | Prevención de solapamientos. |

Estos números no van en servicios ni en el cron del SO. Timestamps de elegibilidad
persistidos evitan extender el plazo en cada ejecución. Cambiar la gracia afecta
nuevos descartes; una modificación retroactiva exige un procedimiento explícito.

Algoritmo propuesto:

1. Seleccionar metadata descartada con plazo cumplido, paginada por fecha/ID;
   índice parcial por `purgar_despues_de, id` para descartados.
2. Por candidato, transacción corta y locks en el mismo orden que confirmación;
   omitir candidatos ocupados y revalidar estado, plazo, intento cerrado y ausencia
   de asociación a anticipo. No cargar `contenido` para decidir elegibilidad.
3. Borrar solo la fila de contenido, marcar registro purgado y guardar evento
   asociado a conversación en la misma transacción. No borrar conversaciones,
   mensajes, registros técnicos ni certificados confirmados.
4. Si no hay contenido, reconciliar idempotentemente sin inventar bytes eliminados.
   Si hay contradicción (descartado vinculado a anticipo), omitir y reportar.
5. Terminar al alcanzar el presupuesto temporal; continuar en la próxima ejecución.
   Acotar timeout SQL/locks para que un candidato no bloquee el Scheduler.

`withoutOverlapping` es una protección adicional; los locks/transacciones de base
resuelven la carrera con confirmación o descargas tardías. Con varios schedulers,
usar ejecución única con un mecanismo compartido validado, no cachés locales.

## Aceptación antes de habilitar

- Simular tiempo: antes, en y después del vencimiento/gracia; cancelación y regreso
  al menú; intento activo excluido; nuevo intento aislado del anterior.
- Probar en PostgreSQL real confirmación contra limpieza, worker tardío,
  confirmación duplicada y caída a mitad de purga; conservar datos en rollback.
- Verificar que promoción no inserta otro binario ni selecciona su contenido.
- Probar límites configurados distintos en textos y bytes, incluyendo cambios de
  config con un intento activo; no usar el default como único caso de test.
- Comando `--dry-run` con candidatos y bytes estimados desde metadata, sin mutación.
- Medir lotes, tiempos, WAL, memoria y crecimiento con carga sintética.

La purga de la base activa no borra copias ya incluidas en backups. La retención
institucional de confirmados y backups sigue pendiente y debe abarcar ambos.
