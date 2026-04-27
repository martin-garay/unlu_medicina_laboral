# Storage y archivos sensibles

## Principio base

Los certificados médicos son archivos sensibles y no deben almacenarse ni servirse desde rutas públicas permanentes.

## Estado actual

El flujo de anticipo/certificado ya registra archivos asociados en `anticipo_certificado_archivos`, pero el storage real sigue siendo metadata-only.

Los campos `storage_disk` y `storage_path` ya existen como base para evolucionar a persistencia real.

## Estrategia esperada

La implementación futura debe:

- usar discos privados de Laravel
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

- driver definitivo para storage privado en ambiente local y producción
- uso de Temporary URLs o Controller Stream
- política de retención de archivos y exportaciones
- tamaño máximo operativo de archivo para backoffice
