# Storage y archivos sensibles

## Principio base

Los certificados médicos son archivos sensibles y no deben almacenarse ni servirse desde rutas públicas permanentes.

## Estado actual

El flujo de anticipo/certificado ya registra archivos asociados en `anticipo_certificado_archivos`, pero el storage real sigue siendo metadata-only.

Los campos `storage_disk` y `storage_path` ya existen como base para evolucionar a persistencia real.

## Decisión vigente — 2026-09-14

Por decisión del usuario, el contenido se guardará en PostgreSQL como `bytea`,
en una tabla separada de `anticipo_certificado_archivos`. Esta última conserva
la metadata consultada por listados y reportes. Reemplaza la propuesta de disco
local como destino inicial; todavía no hay migración ni driver binario implementado.

Los bytes no deben convertirse a Base64 ni incluirse en JSON, logs, payloads de
colas o serializaciones de modelos. El acceso al contenido será explícito, por
identificador y con autorización; la elección final del endpoint sigue pendiente.

### Rendimiento como criterio obligatorio

- Los listados, filtros, reportes y relaciones habituales no seleccionan contenido,
  ni cargan automáticamente la relación de binarios. Ocultarlo al serializar no
  evita el costo de haberlo consultado.
- Indexar identificadores y relaciones según las consultas; no indexar el binario.
- Mantener 3 archivos de 5120 KiB y validar los bytes reales antes de persistir.
- Descargar y validar antes de abrir la transacción de persistencia. No sostener
  transacciones ni locks mientras se espera a Meta.
- Evitar duplicar el contenido al pasar de borrador a definitivo; resolver el
  vínculo transaccional y la idempotencia en el diseño de M6/M7. La tabla actual
  se crea al confirmar, por lo que el vínculo del binario borrador con la
  conversación debe definirse antes de la migración.
- Leer un archivo por operación y medir memoria real de PHP/PDO. Una respuesta
  HTTP en streaming no garantiza lectura incremental de `bytea` desde PostgreSQL.
- Medir latencia de listados y descargas (p95), memoria máxima, conexiones y
  concurrencia con contenido sintético representativo, incluyendo archivos de 5 MiB.
- Conservar contratos de storage para permitir una migración futura a otro backend
  si las mediciones lo justifican, sin cambiar el flujo de negocio.

PostgreSQL utiliza TOAST para valores grandes y puede evitar leerlos cuando no
se seleccionan. Esto no aísla disco, CPU, conexiones ni backups entre tablas.
Referencia: [PostgreSQL 16: TOAST](https://www.postgresql.org/docs/16/storage-toast.html).

La capacidad se estima con cantidad de archivos × tamaño promedio. Por ejemplo,
10.000 archivos de 2 MiB suman aproximadamente 19,5 GiB de contenido; 100.000,
195,3 GiB. Son escenarios, no una previsión de demanda ni límites de PostgreSQL.
Hay que sumar TOAST/índices, WAL, espacio operativo y backups; no asumir ahorro
por compresión de PDFs e imágenes ya comprimidos.

Las pruebas y controles de capacidad, backups y restauración se especifican en
[el plan de despliegue](../../deploy/docs/certificate-storage-rollout.md).
No se declara capacidad productiva validada sin esas mediciones.

## Estrategia esperada

La implementación futura debe:

- usar PostgreSQL con contenido `bytea` en tabla separada
- evitar rutas como `public/storage/certificados`
- impedir URLs públicas permanentes
- validar sesión, usuario y permisos antes de servir archivos
- auditar visualización y descarga
- registrar metadata suficiente para trazabilidad sin exponer contenido médico en logs

## Acceso a archivos

Hay dos estrategias aceptables según el storage elegido:

- Temporary URLs, cuando el driver lo soporte y se generen con expiración corta.
- Controller Stream, validando permisos antes de entregar el archivo.

En ambos casos, el acceso debe estar mediado por backend y auditoría.

## Descargas desde Filament

Una acción visual de Filament para descargar o visualizar certificado debe:

1. validar permiso del operador
2. delegar en una Application Action o servicio de storage
3. registrar auditoría de lectura
4. entregar el archivo sin revelar ruta interna permanente

## Exportaciones

Las exportaciones con información sensible deben:

- generarse en storage privado
- expirar o quedar bajo retención definida
- auditar solicitud y descarga
- ejecutarse por Job si el volumen puede bloquear la UI

## Tests esperados

La implementación de storage privado debe cubrir:

- archivo no accesible sin autenticación
- operador sin permiso no puede visualizar ni descargar
- operador con permiso puede acceder
- cada visualización o descarga registra auditoría
- rutas públicas no exponen archivos sensibles
- errores de archivo ausente o corrupto no filtran datos internos

## Decisiones pendientes

- detalle del esquema y ciclo de vida de binarios borradores/finales; el backend PostgreSQL y la separación de tablas ya están acordados
- uso de Temporary URLs o Controller Stream
- política de retención de archivos y exportaciones
- concurrencia esperada, volumen mensual y objetivos de rendimiento; se mantiene el límite actual de 5120 KiB por archivo
