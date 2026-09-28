# Análisis de preparación para producción

Fecha: 2026-09-28. Este relevamiento es documental y está basado en código,
configuración versionada, planificación y documentación del repositorio. No
consultó `.env`, secretos, datos productivos ni estado de servidores. No autoriza
cambios de runtime ni despliegues.

## Resultado ejecutivo

El repositorio define un procedimiento de despliegue, pero el producto todavía
no debe aceptar trámites reales: la identificación usa mocks permisivos por
defecto, el storage de adjuntos guarda metadata y no bytes, y falta verificar
controles del webhook y la operación real. Además, el estado de catálogos
institucionales y sus responsables está pendiente. Son brechas distintas y no se
resuelven convirtiendo todos los valores de configuración en tablas.

“Preparado en documentación/deploy” no significa desplegado ni validado en un
servidor institucional. El inventario de hosts y credenciales reales no fue
inspeccionado.

## Clasificación de datos y catálogos

La lista completa de C1–C17, consumidores, riesgos históricos y preguntas de
decisión permanece en [inventario de tablas maestras](15-inventario-tablas-maestras.md).
Su estado sigue siendo **por decidir**; este documento no selecciona tablas.

| Clase | Ejemplos observados | ¿Mock? | Criterio para producción |
| --- | --- | --- | --- |
| Catálogo funcional en configuración | sedes, jornadas, tipos de ausentismo/certificado y parentescos (`config/medicina_laboral.php`) | No por definición: son valores explícitos locales, aún sin validar como catálogo institucional | Confirmar fuente, códigos, responsable, vigencia y tratamiento histórico. Decidir caso por caso si permanece en config o pasa a DB. |
| Fixture de integración | legajos 10001/10002 y fallback de registros desconocidos en los dos providers de identificación | Sí; drivers predeterminados son `mock`, y `accept_unknown_legajo` predetermina `true` | No habilitar solicitudes reales con estos providers. Definir y probar proveedor autoritativo, rechazo/falla, permisos y traza antes de cambiar el driver. |
| Datos del trámite / snapshots | nombre, legajo, sede/jornada informados y datos familiares en metadata | No son maestros; son entradas e instantáneas históricas | Preservar lo que el solicitante declaró y la opción mostrada. Definir validación contra fuente confiable sin reescribir trámites históricos. |
| Adaptador nulo de notificaciones | `mail.driver=null` y `NullBusinessNotificationSender` | Stub deliberado | Acordar si el aviso requiere notificación, destinatario, transporte, errores y monitoreo; comprobar configuración efectiva. |
| Adaptador metadata-only de archivos | drivers draft/final aceptan `metadata`; marcan `metadata_only` | Stub deliberado, no persistencia documental | Resolver BO-002/M5. No considerar recibido un certificado si su contenido no se guardó y puede recuperarse de forma privada. |
| Canal real sujeto a configuración | WhatsApp Cloud API usa HTTP cuando tiene credenciales | No es mock por sí mismo | Credenciales faltantes se registran y retornan; fallos HTTP/excepciones no se elevan claramente al flujo. El controlador registra salida como enviada tras invocar el sender. Acordar estados/reintentos/idempotencia y redacción de logs. |
| Catálogos técnicos y acciones de flujo | pasos, estados, eventos, comandos y tipos de mensaje (C6–C16) | No son mock por estar codificados | Mantener transiciones/acciones en código salvo proyecto explícito de motor configurable; no crear CRUD que prometa comportamiento no implementado. |
| Configuración para tests | SQLite `:memory:`, fixtures/fakes en tests, Faker `es_AR` | Test-only | No equivale a entorno productivo. La suite no prueba por sí sola semántica ni operación PostgreSQL productiva. |

Los menús/listas y validadores aún mantienen opciones por separado y algunos
asocian IDs por posición. Al ampliar catálogos se debe corregir esa fuente común y
probar orden, códigos y selecciones; una nueva tabla aislada no evita la
desincronización.

## Otros mocks y límites hallados

- **Identificación del trabajador:** `AppServiceProvider` usa `mock` por defecto
  para `WorkerIdentificationService` y `MapucheWorkerProvider`. Elegir el adapter
  `mapuche` no conecta a Mapuche real mientras `MAPUCHE_DRIVER` siga en `mock`;
  no se encontró implementación externa real en el binding inspeccionado. Los
  mocks permiten aceptar legajos que no existen y devolver campos nulos.
- **Certificados:** el storage implementado es metadata-only. Se puede persistir
  metadata de un adjunto aunque no estén guardados sus bytes. M5/BO-002 ya es el
  plan canónico para resolverlo y permanece bloqueado por decisiones documentadas.
- **Notificación por correo:** driver nulo predeterminado. El adapter de correo
  configurado retorna silenciosamente si no tiene destinatario.
- **WhatsApp:** usa el endpoint Cloud API real configurado, pero el sender no
  convierte un status HTTP no exitoso en fallo de entrega y captura excepciones;
  el controller registra `outgoing_message_sent` luego de la llamada. El webhook
  registra payloads y el controller pasa metadata entrante al flujo. Esto se
  cruza con LOG-001 y los controles de firma del webhook.
- **Chat interno:** hay rutas web y API para la consola interna. En el código de
  rutas inspeccionado no aparece middleware de autenticación específico; antes
  de exponerlo fuera de un entorno local, definir acceso y restricción de red.
- **Compatibilidad de flujos anteriores:** existen handlers transicionales y
  mensajes asociados. Su presencia en el árbol no prueba que sean alcanzables;
  el análisis no hizo recorrido runtime de todas las rutas antiguas.
- **`jsons_resultados`:** no hay archivo o directorio versionado con ese nombre;
  la búsqueda amplia sólo halló usos genéricos de “resultado”. Para auditar un
  conjunto de JSON externo al checkout hace falta identificar su ubicación.

## Brechas y orden recomendado

La secuencia propuesta separa decisiones de negocio, producto y operación. No es
un orden de implementación aprobado.

1. **Definir elegibilidad e identidad:** confirmar fuente institucional, contrato
   de consulta, datos devueltos, reglas para no encontrado/indisponible y campos
   que se muestran/validan. El fallback permisivo debe quedar excluido del
   entorno real. No cargar padrón en una tabla propia sin autoridad y política
   de actualización/PII.
2. **Cerrar certificados y su ciclo documental:** continuar M5 según su daily,
   incluida persistencia de bytes, retención/purga, límites y acceso privado.
   Verificar reintentos, fallo parcial y recuperación de mensajes multimedia.
3. **Acordar catálogos institucionales:** resolver CAT-001 empezando por la
   semántica de sedes y jornada, luego revisar C3–C5 y priorizar C6–C12 si hay
   necesidad de administración. Para cada uno: fuente, owner, clave estable,
   alta/baja, permisos, auditoría, snapshots/backfill y consumidores. Mantener
   C13–C16 cerrados en código salvo razón concreta.
4. **Asegurar entrada/salida:** resolver verificación criptográfica de POST
   Meta, autenticidad, reintentos, duplicados, rate/abuse controls y estados de
   entrega. Definir política de minimización/retención de PII en logs (LOG-001).
   Revisar autenticación/red del chat interno.
5. **Cerrar operación institucional:** SMTP/destinatarios requeridos,
   APP_DEBUG/credenciales, permisos, scheduler, dominio/TLS/firewall, alertas,
   backups y restore-test, rollback y versiones soportadas. Ejecutar el
   runbook contra el inventory real sólo con host/secretos autorizados; no se
   verificó ninguna de esas condiciones remotas en este análisis.

## Gates previos a piloto con datos reales

- Proveedor de identificación real probado, sin aceptación de legajos arbitrarios;
  su degradación no debe simular identidad válida.
- Adjuntos reales preservados, cifrados/protegidos según política aprobada,
  accesibles sólo con autorización y cubiertos por backup/restore y retención.
- Webhook autenticado según el contrato vigente del proveedor; entrada duplicada,
  reintentos y fallo del proveedor probados; secretos/payloads sensibles fuera de
  logs.
- Emisión saliente distingue aceptación, error y entrega pendiente; notificaciones
  requeridas tienen destino/transporte explícitos.
- Catálogos visibles tienen fuente institucional aprobada; cambios no reinterpretan
  conversaciones en curso ni registros históricos.
- Health checks, scheduler, backups/restore, rollback, TLS, red privada de DB,
  acceso a administración y credenciales productivas verificados en entorno
  representativo.

La fuente canónica de gates de infraestructura continúa siendo
[despliegue en producción](../deploy/docs/production-deployment.md); M5 y BO-002
continúan siendo las fuentes de verdad para almacenamiento. Este informe es un
mapa de brechas, no reemplaza esas decisiones ni las dailies.

## Evidencia y límites

Revisión dirigida de `AppServiceProvider`, `config/medicina_laboral.php`, los
providers mock, storage, notificaciones, `WhatsAppSender`,
`WhatsappWebhookController`, `routes/api.php`, `routes/web.php`, inventario C1–C17,
BACKLOG, STATUS y documentos canónicos de deploy. No se leyeron secretos, no se
consultaron servicios externos, no se procesaron datos de producción, no se
ejecutaron tests ni contenedores y no se verificó el servidor remoto.
