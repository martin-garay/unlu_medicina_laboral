# Backend y flujos

Revisado: 2026-09-27. Fuentes: [motor](../05-motor-de-conversacion.md),
[rutas API](../../routes/api.php), [provider](../../app/Providers/AppServiceProvider.php).

- Pasos en `app/Flows/{Aviso,Certificado,Identification}/Handlers/`; contratos y
  resultados en `app/Flows/Common/`; validadores en `app/Flows/Validators/`.
- `app/Services/Conversation/` concentra entrada normalizada, resolución,
  interacción y contexto; servicios de mensajes/eventos/timeouts en `app/Services/`.
- Punto compartido al agregar pasos: registro del provider. Asignar un único owner
  cuando dos workers agreguen handlers. `StepResult` es contrato transversal.
- Los canales comparten lógica de flujo pero no todas las responsabilidades del
  adapter: verificar persistencia y envío en el canal bajo prueba, no presumirlos.
- Catálogos de opciones de negocio (sedes, ausentismo, certificado, parentesco) en
  tablas DB consultadas mediante `app/Services/Catalogos/ChatCatalogService.php`;
  menú y validación comparten orden/estado y snapshot por conversación. Menú
  principal/keywords y otros parámetros siguen en `config/medicina_laboral.php`;
  textos de prompt en `lang/es/`;
  resúmenes largos en `resources/views/messages/`. Ver [mensajes](../10-mensajes-y-templates.md).
- Aviso familiar: [flujo](../06-flujo-aviso-ausencia.md#caso-especial-familiar-enfermo).
  Nombre/parentesco obligatorios; estado provisional anterior se puede retomar.
- Certificados: driver metadata actual en provider/config; una decisión de storage
  futuro no significa que recepción/descarga binaria esté implementada.
- Identificación: interfaces en `app/Services/WorkerIdentification/Contracts/`
  y `Mapuche/Contracts/`; mocks no son padrón real. No acoplar handlers a proveedor.

Antes de extender un contrato, buscar consumidores en controllers, servicios,
handlers y tests; acordarlo antes de paralelizar. No convertir el motor entero en
un editor DB por agregar un catálogo: [inventario CAT-001](../15-inventario-tablas-maestras.md).
