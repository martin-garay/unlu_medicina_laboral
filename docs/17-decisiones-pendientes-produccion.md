# Decisiones pendientes para tablas maestras y producción

Fecha: 2026-09-30. Resultado integrado de PR-002. Es un análisis de evidencia
versionada y recomendaciones; no aprueba tablas, políticas ni despliegues.

## Tablas maestras C1–C17

Fuente detallada: [inventario de tablas maestras](15-inventario-tablas-maestras.md).
Los estados de C1–C5 reflejan la selección y el alcance implementado en CAT-001.
“Config/código” conserva su recomendación para los demás candidatos.

| ID | Candidato | Recomendación | Razón / condición pendiente |
| --- | --- | --- | --- |
| C1 | Sedes / centros | DB + CRUD | CAT-001 crea tabla/admin/chat. Seed conserva las tres opciones anteriores; falta validar e importar lista institucional completa. |
| C2 | Jornada laboral | Texto libre | Decisión del usuario; no se crea tabla ni opciones cerradas. |
| C3 | Tipos de ausentismo | DB + CRUD | CAT-001 agrega `requiere_datos_familiar`; el código estable identifica la opción. |
| C4 | Tipos de certificado | DB + CRUD | CAT-001 lo incluye por ser catálogo visible mantenible en admin y consumido por el chat. No confundir con MIME/extensiones. |
| C5 | Parentescos | DB + CRUD | CAT-001 lo incluye para administrar las opciones del flujo familiar; su etiqueta se conserva como snapshot. |
| C6 | Menús/opciones | Config/código | Navegación y acciones ligadas a handlers e IDs de interacción. No ofrecer editor de flujos por crear tablas. |
| C7 | Aliases/comandos | Config/código | Enrutamiento y compatibilidad; los números dependen del menú mostrado. |
| C8 | Mensajes | Config/código | La fuente vigente es traducciones/MessageResolver; DB requeriría idioma, permisos y versionado. |
| C9 | Plantillas | Config/código | Las plantillas usan Blade; no almacenar ni ejecutar PHP/Blade arbitrario desde DB. |
| C10 | Parámetros funcionales | Diferir | Agrupa políticas distintas y se solapa con BO-002. Separar settings por ambiente, negocio y certificados antes de modelar. |
| C11 | Formatos adjuntos | Config/código | Extensiones/MIME son reglas técnicas de validación; no duplicarlas como catálogo y settings. |
| C12 | Notificaciones/destinatarios | Config/código | Destinatario y transporte dependen del entorno; no hay evidencia de ruteo múltiple. Secretos quedan fuera de tablas de negocio. |
| C13 | Estados de aviso/certificado | Config/código | Transiciones y efectos pertenecen al código. Sólo evaluar etiquetas administrables ante una necesidad concreta. |
| C14 | Estados/orígenes de vínculos | Config/código | La pivot ya existe; mantener sus valores cerrados y alinear cambios con BO-003. |
| C15 | Estados/rechazos de archivos | Diferir | Inventariar valores realmente persistidos y resolver BO-002 antes de normalizar o crear FKs. |
| C16 | Canales, pasos, eventos y cierres | Config/código | Dominios técnicos: una fila nueva no implementa su comportamiento. No CRUD libre. |
| C17 | Trabajadores/cache externa | Diferir | Los registros actuales son mocks, no padrón. No replicar PII sin fuente autoritativa, contrato, sincronización y retención. |

### Decisiones de catálogo pendientes

1. Para C1, obtener lista oficial de centros, códigos, fuente y reglas de vigencia;
   mapear central/campus/delegación sólo si existe equivalencia documentada.
2. Confirmar la validez institucional de valores y códigos iniciales de los catálogos.
3. Mantener el snapshot de opciones enviado en la conversación activa: el código y
   la etiqueta presentados siguen siendo válidos aunque luego se desactive o renombre
   el registro. Las selecciones persistidas conservan su etiqueta en metadata.
4. Mantener fuera de DB C6–C17 salvo decisión posterior documentada.

## Certificados — decisiones M5 / BO-002

D1 está aprobada: PostgreSQL `bytea` en tabla separada y cola PostgreSQL desde la
primera versión. M5 sigue `blocked` por decisiones restantes; valores en docs/config
que se describen como propuestas no son políticas aprobadas.
Fuentes canónicas: [plan incremental de certificados](14-certificados-problematica-y-plan-incremental.md),
[matriz de settings](backoffice/certificate-storage-settings.md) y
[ciclo de vida](backoffice/certificate-attachment-lifecycle.md).

| Decisión | Estado y opciones | Cierre requerido |
| --- | --- | --- |
| D2 Acceso a confirmados | Propuesta: descarga autenticada mínima, vista previa diferida. Elegir endpoint con permisos o acceso temporal. | Definir alcance, autorización por recurso, permisos y auditoría; probar permitido/denegado. |
| D3 Retención/purga | Pendientes las reglas por separado para borradores vencidos/cancelados, parciales/huérfanos, confirmados y backups. Gracia de 24 h, ejecución horaria y lote 100 son propuestas. | Aprobar categorías, plazos, gracia y alcance de purga; definir dry-run y no activar eliminación retroactiva implícita. Confirmados/backups requieren política institucional. |
| D4 Settings | La fuente acordada para la primera implementación es `config/*.php`; la UI administrativa queda para una etapa futura. Siguen pendientes parámetros iniciales de workers/reintentos/timeout/concurrencia, defaults aprobados y cierre de la matriz. | Completar tipo, unidad, rango, default, fuente, permisos, vigencia y aplicación; validar coherencia entre chat, web, workers y scheduler. |
| D5 Capacidad | No hay volumen, concurrencia ni objetivos de recuperación acordados ni medidos. | Acordar escenarios y umbrales; M8/M9 deben medir capacidad, backup/restore, latencia, memoria y crecimiento. |

Ningún parámetro debe caer silenciosamente a metadata-only como si los bytes
hubieran quedado preservados. No hay autorización para runtime, purga ni pase de
M5 a M6–M9 hasta resolver las decisiones de M5 y sus gates.

## Seguridad, integraciones y operación

Síntesis de [brechas de preparación](16-preparacion-produccion.md) y gates de
[despliegue productivo](../deploy/docs/production-deployment.md); las fuentes
canónicas mantienen el detalle y precedencia operativa.

| Gate | Evidencia del repo | Condición antes de piloto real | Dependencia externa |
| --- | --- | --- | --- |
| Firma del webhook | El GET valida verify token; el POST revisado no muestra validación de firma. Deploy exige verificarla. | Validar criptográficamente POST; probar firma válida/inválida, reintentos y duplicados; minimizar payloads en logs. | Contrato vigente, secretos y operación Meta. |
| Chat interno | Rutas web/API no declaran middleware de autenticación específico en esos archivos. No se verificó middleware global/proxy. | Definir audiencia; proteger con auth/authorization o red y probar send/reset. | Usuarios autorizados y redes institucionales. |
| Envío WhatsApp | Sender `void` registra respuesta/excepción sin propagar fallo; el controller registra `outgoing_message_sent` tras invocarlo. | Distinguir sin credenciales, error, aceptación y entrega; definir callbacks/reintentos/idempotencia y verificarlos. | Credenciales, monitoreo y contrato del proveedor. |
| Logs | Webhook registra payload entrante completo; sender registra destinatario, payload, respuesta y errores. LOG-001 está pendiente. | Aprobar campos permitidos, redacción, acceso y retención; revisar entrada/salida/errores. | Política institucional de PII y conservación. |
| Identidad laboral | Drivers por defecto son mocks permisivos; Mapuche adapter no prueba conexión a sistema real. | Integrar fuente autorizada; legajo desconocido o proveedor caído no puede producir identidad válida. Definir datos/errores/traza. | Fuente, acceso, contrato y responsable de Universidad. |
| Notificación de negocio | Sender nulo es default; el mail configurado no hace nada sin destinatario. | Confirmar si notificación es obligatoria y, si sí, definir destino/transporte, fallos y monitoreo. | Destinatario y servicio de correo institucional. |
| Despliegue | Documentación define DNS/TLS, Vault, `APP_DEBUG=false`, firewall, PostgreSQL privado, backups/restore, rollback, health y scheduler. Servidor real no se inspeccionó. | Ejecutar runbook con inventario autorizado y evidenciar gates en un entorno representativo. | Hosts, redes, secretos, ventanas y responsables. |

Los hallazgos reflejan el código/configuración revisados, no la configuración efectiva
de middleware, `.env`, proxy ni servidor. El checklist canónico está en
[despliegue productivo](../deploy/docs/production-deployment.md); no se verificó
ningún host remoto en PR-002.

## Próximos pasos

- **CAT-002:** incorporar la lista institucional completa de sedes cuando se obtenga
  una fuente autorizada. Jornada queda libre; C6–C17 no forman parte del CRUD
  implementado.
- **M5:** resolver D2–D5 en su daily canónico antes de trabajo runtime M6–M9.
- **Piloto:** satisfacer gates de identidad, archivos, webhook, logs, salida y
  operación; la documentación de deploy no demuestra readiness del servidor.

Este informe responde las decisiones y evidencias conocidas en el repo; los datos
institucionales, políticas de retención y credenciales permanecen pendientes.
