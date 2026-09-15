# Certificados: problemática y plan incremental

Fecha: 2026-09-14. Estado: propuesta de implementación en elaboración conjunta.
No implica implementación, activación de limpieza ni autorización de producción.
M5 continúa abierto en el [daily activo](../plan_dev/daily/2026-09-14.md).

## Propósito

Entregar primero un circuito funcional y verificable en testing: recibir un PDF o
imagen por WhatsApp, validar, guardar bytes privados, confirmar el anticipo y
recuperar el archivo con autorización. Evolucionar después según necesidad y
mediciones. La primera entrega no incluye toda la administración ni infraestructura
para una escala hipotética, pero sí consistencia, configuración y trazabilidad.

Este documento explica el problema y ordena decisiones/entregas. No duplica los
contratos detallados de [settings](backoffice/certificate-storage-settings.md),
[ciclo de vida](backoffice/certificate-attachment-lifecycle.md) y
[storage](backoffice/storage-and-sensitive-files.md). La operación canónica vive en
[deploy](../deploy/docs/certificate-storage-rollout.md).

## Problemática actual

### Referencias sin contenido durable

El runtime registra metadata, no descarga ni almacena el PDF/imagen. Por tanto,
registrar un anticipo no acredita disponibilidad del documento. La URL de media
no debe usarse como archivo permanente. Los históricos metadata-only necesitan
una representación explícita; no se puede reconstruir contenido inexistente.

### Contrato inconsistente de límites

Hay configuración de cantidad, formatos y tamaño, pero el handler no aplica el
límite de bytes y el mensaje de adjuntar no lo informa. Debemos alinear texto,
validación y almacenamiento con una única política. Cambiar settings durante un
intento no puede volver engañosa la instrucción ya enviada. Las listas y defaults
no deben repetirse en servicios, templates ni consumidores.

### Recepción no equivale a confirmación

Un adjunto puede llegar a un intento luego cancelado o vencido. No corresponde
crear una entidad de negocio definitiva al recibirlo. Una conversación también
puede volver al menú y comenzar otro intento: hay que preservar identidad de cada
intento para que sus adjuntos y trabajos tardíos no se mezclen.

### Consistencia y fallos concurrentes

Descarga, confirmación, reentrega de Meta y limpieza pueden competir. Debemos
impedir duplicados, confirmaciones sin bytes y borrado de documentos confirmados.
Una transacción SQL no debe mantenerse abierta esperando la red. Si hay cola,
registrar un mensaje y perder el job no puede dejar el archivo pendiente para
siempre; se necesita publicación durable o recuperación verificable.

### Acumulación y eliminación

Los borradores abandonados consumen espacio. Su expiración funcional debe
separarse de la purga del contenido. La limpieza debe tener gracia, lotes y tiempos
configurables, conservar metadata/eventos y excluir intentos vigentes. Cancelar
el subflujo también importa, aunque la conversación siga abierta. La retención de
confirmados y backups requiere una política propia.

### Rendimiento y recuperación

Guardar `bytea` separado evita cargar bytes en listados, pero la base comparte
CPU, conexiones e I/O. Descargas simultáneas, memoria PHP/PDO, WAL y backups pueden
ser más limitantes que la cantidad de filas. No hay una capacidad validada aún.
Necesitamos medir carga y restore con datos sintéticos antes de prometer capacidad.

### Acceso y operación

El binario requiere permisos por recurso y auditoría de lectura. El sistema puede
ser funcional sin preview sofisticado, pero debe existir un acceso autorizado
mínimo para comprobar y utilizar el documento. Hoy no existe UI de settings ni
base de cola propia (`config/queue.php` y migraciones de jobs ausentes al revisar).
No debemos asumir que workers, limpieza o descargas ya están operativos.

## Decisiones acordadas

- PostgreSQL `bytea`, contenido separado de la metadata, sin Base64 en persistencia.
- Políticas y parámetros configurables; una fuente efectiva para chat y storage.
- Rendimiento como requisito de implementación y aceptación.
- Conservar conversaciones, mensajes y eventos; no confundirlos con los binarios.
- Planificar entrega funcional inicial y evolución por pasos, tomando decisiones
  con el usuario. Ninguna recomendación siguiente cuenta como decisión aceptada.

## Registro de decisiones a tomar, en orden

| ID | Decisión | Recomendación para primera entrega | Estado |
| --- | --- | --- | --- |
| D1 | Ejecución de descarga | Cola PostgreSQL y worker acotado; evita esperar al archivo dentro del webhook | Preguntada, pendiente de respuesta |
| D2 | Definición de funcional | Incluir descarga autenticada mínima de confirmados con permiso/auditoría; posponer preview | Propuesta; promover corte de I4/P2 explícitamente |
| D3 | Borradores vencidos/cancelados | Scheduler, gracia configurable; 24 h propuesta, no aprobada; confirmados sin purga automática en testing | Pendiente |
| D4 | Parámetros iniciales | Reutilizar defaults actuales de tamaño/cantidad; completar timeouts, reintentos, lotes y validaciones compatibles | Pendiente, después de D1 |
| D5 | Capacidad y aceptación | Acordar volumen y concurrencia de testing; medir p95, memoria, crecimiento y restore | Pendiente |

D1 alternativa: descarga directa con timeout estricto. Reduce operación inicial,
pero alarga el webhook y obliga igualmente a tratar duplicados y recuperación.
Si se elige, medir el presupuesto de respuesta antes de validarla; no asumir que
funciona por tratarse de archivos pequeños. La abstracción del downloader permite
migrar a cola después. El resto del diseño de binarios no depende de esa elección.

## Etapa 0 — Cerrar decisiones mínimas de M5

Resultado: contrato concreto de primera entrega, con D1–D5 resueltas y catálogo
inicial de settings. No exigir aquí una UI administrativa o políticas de escalado
que solo serán necesarias en etapas posteriores. Toda función incorporada debe
usar config desde su primer corte.

Archivos: este documento, contratos enlazados, daily, STATUS y plan de deploy.
Automática: enlaces locales, `git diff --check`, `bin/check-docs`.
Manual: recorrer juntos un ejemplo de éxito, cancelación y fallo de descarga.
Stop: falta modalidad, política de descarte o definición de acceso mínimo.

## Etapa 1 — Circuito funcional inicial

Descomponer en commits pequeños y validar cada corte. M6/M7 conservan el trabajo
funcional; acceso mínimo requiere promover D2 del plan I4/P2, sin duplicarlo.

| Corte | Implementación | Validación automática obligatoria | Manual sugerida / stop |
| --- | --- | --- | --- |
| 1A: política común | Servicio de límites efectivos y snapshot de intento; textos parametrizados y validación de configuración | Tests con valores distintos al default; mensaje y validador coinciden; máximo exacto y máximo + 1 byte | Cambiar config y revisar chat; stop ante discrepancias |
| 1B: persistencia | Metadata desde borrador, FK nullable, contenido 1:1, estados técnicos, compatibilidad histórica y filtros administrativos | PostgreSQL real: bytes/hash, constraints, rollback y listados sin contenido/borradores | Revisar histórico y borrador; stop si se expone como confirmado |
| 1C: descarga | Cliente mockeable, validación de bytes/MIME, registro de fallos; cola/worker si D1 lo acuerda | HTTP fake más PostgreSQL; reentrega, descarga tardía, fallo de job/DB, timeout y tamaño sin Content-Length | PDF/foto sintéticos; stop si falla como éxito o queda pendiente sin recuperación |
| 1D: confirmación | Vincular registro disponible y estado sin copiar bytes; cancelación al menú e inactividad | Carreras, confirmación duplicada, sin INSERT extra de contenido y sin anticipo cuando faltan bytes | Recorrer confirmar/cancelar; stop ante pérdida o duplicación |
| 1E: acceso mínimo | Endpoint autenticado con permiso por recurso y auditoría; acción de descarga mínima si se acuerda D2 | Acceso permitido/denegado, recurso ausente y auditoría; no filtrar bytes en JSON/logs | Operador descarga y verifica hash; stop ante acceso indebido |
| 1F: limpieza | Scheduler, selección por metadata, gracia, lotes, locks, dry-run y evento; bytes eliminados sin borrar historial | Reloj simulado, borde de gracia, confirmar vs purgar, worker tardío y rollback | Dry-run y purga sintética; stop si toca activo/confirmado |

Áreas de archivos: `config/`, `lang/es/`, `app/Services/Storage/`, handlers,
servicio de anticipo, modelos/migraciones, comandos y `routes/console.php`;
si D1/D2 se acuerdan, jobs/queue y controller/policies/acción de backoffice.
Actualizar docs funcionales y diagramas junto con cada cambio estructural real.

Ejecutar suite `make test` en cortes funcionales; no usar SQLite como única prueba
de `bytea`/concurrencia. DBML propuesto aún no sustituye el esquema de runtime.

Funcional inicial significa que todos estos cortes están integrados y validados,
no solo que 1A o 1B pasaron tests. La limpieza puede probarse deshabilitada y en
dry-run durante preparación; habilitarla en testing requiere D3 y aceptación.

## Etapa 2 — Aceptación operativa de la primera entrega

Corresponde a M8/M9; ver [despliegue](../deploy/docs/certificate-storage-rollout.md).
El objetivo es una entrega funcional en testing, no producción automática.

Incluye migraciones, config efectiva, worker si aplica, Scheduler, conectividad
real de runtime, backup/restore con bytes, release y rollback compatibles.
Automáticas: suite funcional aprobada; checks de deploy; convergencia/idempotencia;
pruebas acotadas de carga y restauración con hashes según objetivos D5.
Manual: WhatsApp real → adjunto sintético → confirmación → descarga autorizada;
rechazo por tamaño/tipo y fallo de red sin falso éxito. Cancelación y limpieza.
Stop: sin conectividad de media, archivos inaccesibles, restore fallido o efectos
sobre chat/panel fuera del objetivo acordado. Registrar evidencia sin datos médicos.

## Etapa 3 — Administración y mejoras por uso

Tras la primera entrega: UI de settings con permisos/auditoría/versionado, preview,
operación de fallidos y reportes de capacidad. Priorizar según feedback, sin
implementar otra fuente de límites. Los parámetros ya existen en config desde la
primera entrega; esta etapa agrega su administración visual.

También evaluar cuotas agregadas por usuario/global y reservas según mediciones.
Desde el inicio sí se necesitan límites por intento, idempotencia, concurrencia
acotada y monitoreo de capacidad. Posponer cuotas avanzadas requiere acordar el
alcance de D5; no retirar en silencio un requisito vigente de la matriz.

Automáticas: permisos, invalidación de caché, coherencia de settings con intentos
activos, cuotas si se incorporan y regresión funcional. Manual: operador cambia
una política y verifica efecto esperado. Stop: diferencias entre procesos o
cambios retroactivos de retención no acordados.

## Etapa 4 — Escalar cuando la medición lo requiera

Revisar recursos/concurrencia, índices y mantenimiento si crece la latencia o el
backlog. Evaluar backend externo solo cuando crecimiento, ventana de backup o
restore excedan objetivos, conservando contratos y referencias por archivo.
No elegir almacenamiento externo, particionado ni caché de binarios sin evidencia.

Automáticas: benchmark comparativo, migración por lotes con hashes, compatibilidad
de lectura y rollback. Manual: validar operación y recuperación. Stop: pérdida de
trazabilidad o que la alternativa no resuelva el cuello de botella medido.

## Condiciones de producción

La primera entrega de testing no aprueba políticas productivas. Retención de
confirmados/backups, firma de webhook, permisos, capacidad, recuperación y demás
condiciones institucionales se validan conforme a documentación de despliegue.

## Estado y siguiente conversación

Documentación creada; no hay implementación nueva. D1 es la primera pregunta
pendiente. Después se resuelven D2/D3, se proponen valores coherentes de D4 y se
acuerda D5. Al aceptar cada decisión se actualiza el registro y sus contratos,
sin reiniciar ni duplicar la planificación en documentos paralelos.
