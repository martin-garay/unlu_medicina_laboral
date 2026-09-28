# Arquitectura: mapa verificado

Revisado: 2026-09-27. Fuentes: [composer.json](../../composer.json),
[arquitectura](../03-arquitectura.md), [motor](../05-motor-de-conversacion.md).
Parte de esos documentos describe etapas previas/propuestas; contrastar runtime.

- Laravel 11; `composer.json` pide PHP ^8.2; Docker local usa PHP 8.3.
- Entradas HTTP en `app/Http/Controllers/`; WhatsApp y chat interno construyen
  `ConversationInboundMessage` para `ConversationInteractionService`.
- `ConversationFlowResolver` selecciona handlers registrados en
  `app/Providers/AppServiceProvider.php`; cada handler devuelve `StepResult`.
- Servicios de negocio materializan `Aviso`/`AnticipoCertificado`; conversación,
  mensajes y eventos son entidades separadas. No confundir borrador y registro.
- UI: Blade para chat; Filament para backoffice. Ver [frontend](frontend.md).
- Scheduler en [routes/console.php](../../routes/console.php); no scheduler externo
  nuevo para inactividad. Integraciones tras interfaces, drivers en config.
- Esquema implementado: migraciones/modelos; DBML tiene fuentes actuales y
  propuestas explícitas. Ver [database](database.md).
- Despliegue/operación: [deploy/README](../../deploy/README.md), sin copiar runbooks.

Para localizar componentes buscar nombre de clase o `stepKey`, no leer todos los
handlers. Diagrama de relaciones: [conversation-engine.puml](../diagrams/classes/conversation-engine.puml).
