# Configuración y cierre de diseño del almacenamiento

Estado: contrato documental de M5, 2026-09-14. No crea settings de runtime ni UI.
Requisito del usuario: toda política y parámetro operativo debe ser configurable,
sin valores dispersos en handlers, servicios, comandos, mensajes o tests.

## D1 aprobada — 2026-09-14

Cola PostgreSQL desde la primera versión. La modalidad inicial ya está elegida;
las claves y valores operativos de jobs/worker se completan en D4 del
[plan incremental](../14-certificados-problematica-y-plan-incremental.md).
No se implementarán simultáneamente dos recorridos de descarga para satisfacer
un setting: el driver elegido debe estar soportado y validado antes de activarlo.

## Fuente de verdad y ciclo de cambios

- Primera implementación: `config/*.php`, reutilizando claves existentes. `env()`
  únicamente en config; valores por entorno desde despliegue. Catálogos/listas
  estructuradas viven en config, sin exigir codificaciones ad hoc en variables.
- Defaults documentados en config, nunca repetidos como fallback en consumidores.
  No agregar un segundo max_size o max_files específico para storage.
- Servicio de política común para chat, validador, descarga y persistencia.
- Inventariar cada clave con tipo, unidad, rango, default, fuente, sensibilidad,
  permiso de cambio, alcance y momento en que aplica. Validar antes de activar.
- Política de negocio versionada por intento: tamaño, cantidad, formatos y
  snapshot del catálogo; guardar versión/valores efectivos sin secretos.
- La gracia se toma al descartar y queda persistida como fecha de elegibilidad.
  Los límites de ejecución se toman al inicio de cada corrida. Cambios de backend
  requieren migración compatible y no reinterpretan archivos existentes.
- Cambio de config por deploy: regenerar caché y reiniciar procesos persistentes
  cuando corresponda; pruebas deben verificar que web, worker y scheduler coinciden.
- UI futura: un único proveedor de settings efectivos, permisos y auditoría de
  cambios, validación e invalidación de caché. Documentar precedencia antes de
  introducir valores en DB; no mezclar lecturas directas de DB, config y env.
- El panel de configuración sigue en P3 del daily de backoffice; este contrato no
  declara esa pantalla existente ni amplía M6 para implementarla.
- Mensajes en `lang/es`, textos largos en Blade, con parámetros resueltos desde
  la política efectiva. Un cambio del límite no requiere editar el texto.

## Matriz obligatoria para M5

Las familias nuevas son nombres propuestos a definir en el catálogo final;
no representan claves implementadas. Las claves existentes se conservan.

| Familia / fuente | Parámetros | Aplicación |
| --- | --- | --- |
| `certificados` existente | `max_files`, `max_size_kb` (KiB), MIME, extensiones y tipos entrantes | Misma política en chat y bytes; snapshot por intento. |
| `conversation` y `certificados` existentes | Intentos inválidos, inactividad, plazo de aviso, palabras/menús de cancelación | Reutilizar política conversacional; no crear timeout rival para borradores. |
| Política de capacidad nueva | Bytes máximos pendientes por intento/usuario y presupuesto global, concurrencia de recepción/descarga | Contabilizar pendientes además de archivos completos; aplicar reserva transaccional para evitar excedentes simultáneos. |
| Descarga nueva | Modalidad, conexión HTTP, tiempo por petición/trabajo, intentos, esperas, vigencia del trabajo, tamaño del bloque, hosts permitidos y redirecciones | Operación validada por entorno; URL nueva por ID de media cuando expire; secretos fuera del snapshot. |
| Cola PostgreSQL acordada | Conexión, nombre, workers, concurrencia, timeout, retry_after, lease, intentos y recuperación de fallidos | No duplicar reintentos HTTP y de job sin un presupuesto total definido. |
| `storage` existente/ampliado | Drivers de borrador/final, conexión de DB, compatibilidad metadata-only | Registrar backend por archivo; cambiar default no cambia referencias anteriores. |
| `certificados.cleanup` propuesta | Habilitación, gracia, expresión de calendario, zona horaria, lote, tiempo máximo, lock y timeout SQL | Parámetros configurables; dry-run disponible; no deriva retención de confirmados. |
| Retención | Tratamiento por descarte/vencimiento/cancelación, parciales, huérfanos, confirmados y backups | Explicitar qué políticas se habilitan; sin plazo aprobado no activar purga para esa categoría. |
| Acceso administrativo | Mecanismo, permisos del catálogo, tasa/concurrencia y duración si hay enlaces temporales | Permisos y auditoría siempre obligatorios. Definir acceso a borradores separado de confirmados. |
| Observabilidad/despliegue | Alertas de espacio, crecimiento, atraso de cola/purga, latencia, memoria, ventana backup y objetivos de recuperación | Settings operativos bajo `deploy/`; medir antes de asignar umbrales productivos. |

Los estados, FKs, unicidad, autorización y conservación de trazabilidad son
invariantes del diseño, no switches para desactivarlos. Centralizar estados en
un enum/contrato evita strings dispersos; no hacer editable la máquina de estados
como si fuera un catálogo de tamaños. Los límites del proveedor siguen siendo
restricciones externas: configurar un tamaño mayor no amplía la capacidad de Meta.

## Validación de settings

- Tipos y unidades explícitos, rangos válidos, listas no vacías y MIME/extensiones
  coherentes; rechazar configuración inválida con diagnóstico antes de habilitar.
- Evitar conversiones ambiguas entre MB/MiB/KiB. La comparación se realiza en bytes.
- Límites por archivo/intento y cuotas compatibles; controlar aumentos respecto de
  memoria, límites HTTP, capacidad de DB y políticas activas anunciadas al usuario.
- El timeout de job debe cubrir el trabajo permitido y ser menor que retry_after;
  el lock de scheduler debe cubrir su ejecución acotada. Prever caída/lease vencida.
- Cambios de retención no provocan purgas retroactivas implícitas. Requieren
  simulación y una operación explícita si afectan fechas ya persistidas.
- Una allowlist configurable no habilita URLs arbitrarias aportadas por el usuario;
  validar HTTPS, destino y redirecciones antes de enviar credenciales a media.
- Una configuración incompatible impide habilitar la capacidad afectada; no cae
  silenciosamente a metadata-only simulando que el binario quedó guardado.

## Qué falta para cerrar M5

| Tema | Falta resolver / documentar | Implementación posterior |
| --- | --- | --- |
| Descarga | Modalidad aprobada: cola PostgreSQL. Completar parámetros, publicación atómica o outbox transaccional y recuperación de trabajos/notificaciones | M6 y M8 |
| Retención | Acordar gracia de borradores, cancelados, parciales/huérfanos y política de confirmados/backups; los valores previos son propuestas | M7–M9; política productiva antes de producción |
| Acceso | Seleccionar endpoint autenticado o mecanismo temporal, matriz de permisos y auditoría | I4/P2 de backoffice |
| Catálogo | Completar nombres, defaults y validaciones de la matriz; decidir parámetros de entorno vs UI futura | M6–M8; UI en P3 |
| Consistencia | Revisar transición borrador/final, cancelación al menú, reservas de cuota, reentregas y recuperación tras caídas; sin duplicar bytes | M6/M7 con tests PostgreSQL |
| Migración | FK nullable y cambios de listados/policies; distinguir metadata-only, no inventar contenido histórico ni fecha de purga | M6/M7 |
| Capacidad | Estimar volumen/tamaño/concurrencia y establecer objetivos y escenarios de aceptación | Medición M8/M9 |
| Integración | Comprobar descarga real de Meta y errores; firma del webhook antes de producción según pendiente vigente | M9 / endurecimiento |

M5 cierra con decisiones y criterios verificables, no con todo el runtime terminado.
No exige terminar ahora la UI ni ejecutar ya las pruebas de carga, pero sí define
qué se debe probar y qué condiciones bloquean habilitar cada entorno.

## Evidencia exigida en implementación

- Variar cantidad, tamaño, formatos, gracia, calendario y lote en tests; verificar
  mismo valor anunciado y aplicado, máximo exacto y máximo + 1 byte.
- Cambiar settings con intento activo, proceso persistente y purga ya programada.
- Probar confirmación contra limpieza, worker tardío, reentrega y reinicio tras
  fallo entre persistencia/dispatch/respuesta; nada queda confirmado sin bytes.
- Simular disco/DB/HTTP fallidos, límite de cuota, medio expirado y destino inválido;
  mensajes claros y sin secretos ni contenido en logs.
- Asegurar que borradores no entran en listados finales y que accesos tienen permisos.
- Verificar binario real PostgreSQL, igualdad por hash, promoción sin INSERT de otro
  contenido y consultas habituales sin SELECT de `bytea`.
- M8/M9: medir carga, backups, restore y rollback compatibles; registro canónico en
  [plan de despliegue](../../deploy/docs/certificate-storage-rollout.md).
