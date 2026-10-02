# Status

Snapshot consolidado + última ejecución. Objetivos en MASTER_PLAN; orden y tareas
en daily; pendientes detallados en BACKLOG. El historial no compite con este estado.

## Fecha de última actualización

2026-10-02 -03

## Última ejecución — CAT-MENU-001

- [Daily 2026-10-02](daily/2026-10-02.md): `done`; texto de apertura localizado
  por catálogo. Sedes muestra «Ver sedes» en lista, parentescos conserva su texto.
- Reglas de presentación, opciones e IDs preservados. 7 tests / 29 assertions,
  Pint en archivos modificados y diff check correctos; revisión propia acotada.
- Git inicial limpio; sin push/deploy. Memoria IGNORE; CERT-001 conserva sus pendientes.

### Ejecución anterior — CERT-001

- [Daily 2026-10-02](daily/2026-10-02.md): `needs_review` por aceptación manual
  en testing; código integrado y review independiente sin BLOCKER/HIGH.
- Driver pgsql opt-in: descarga en cola, bytea privado, vínculo por conversación/
  intento/mensaje, confirmación con bytes y descarga admin con permiso/auditoría.
  Purga desactivada; históricos metadata-only conservados.
- 254 tests / 1135 assertions, check-deploy y YAML correctos. Integración sintética
  PostgreSQL 16 correcta; falta Meta real, worker systemd remoto y capacidad/PG17.
- Rollout en deploy/docs/certificate-storage-rollout.md; no push ni deploy.
- Hallazgo de creación inicial concurrente en BACKLOG CERT-CONC-001.

### Ejecución anterior — WA-CACHE-001

- [Daily 2026-10-02](daily/2026-10-02.md): `needs_review`, corrección local para
  recargar PHP-FPM si cambia `.env` aunque el release no cambie.
- Usuario confirma que refrescar caché resolvió el 403 de verificación de Meta.
- Pendientes: review independiente y validación remota; sin push/deploy en este corte.

## Última ejecución — WA-PROXY-001

- [Daily 2026-10-01](daily/2026-10-01.md): `needs_review`; implementado
  `WHATSAPP_PROXY_UNLU` en config/sender y deploy de testing, con proxy explícito.
- 8 tests / 39 assertions y check-deploy pasan. Review propio; falta independiente
  y prueba real vía PHP-FPM/Meta. Sin push ni deploy remoto.
- Usuario confirma TLS aplicado dos veces y `/up` 200 con certificado validado.
- Runbook incluye release nuevo, revisión de migraciones y backup previo.

### TLS-001

- [Daily 2026-10-01](daily/2026-10-01.md): `needs_review`, cambio local completo;
  falta revisión independiente. Prioridad explícita del usuario, sin alterar M5.
- Testing usa `provided` y rutas institucionales de Let's Encrypt; no se escribe
  material TLS en ese modo. `local_ca` respalda archivos antes de reemplazarlos.
- `bin/check-deploy` y diff check correctos; sin ejecución remota ni push.
- Comandos de activación TLS en runbook; release aplicativo conservado.

## Última ejecución — PR-002

- Daily: [2026-09-29](daily/2026-09-29.md), cerrado el 2026-09-30.
- Resultado: `done`, fase `DONE`; se completaron tres revisiones paralelas
  documentales y una síntesis independiente para M5, CAT-001 y gates productivos.
- C1 y C3 quedan como candidatos DB condicionados; C2 diferido; C4–C9, C11–C14
  y C16 en config/código; C10, C15 y C17 diferidos. No hay tablas aprobadas.
- Se confirmó config `config/*.php` como fuente acordada inicial para M5; siguen
  pendientes los valores D2–D5. M5 continúa `blocked`.
- Reviewer independiente: tres findings MEDIUM/LOW resueltos; sin findings nuevos.
- `git diff --check` y referencias locales verificadas. Sin tests/runtime/deploy,
  secretos ni consultas remotas.
- Commit de cierre trazable; árbol limpio tras el registro.

## Última ejecución — CAT-001

- Daily: [2026-09-30](daily/2026-09-30.md), cerrado el 2026-09-30.
- Resultado: `done`; cuatro catálogos de negocio (`sedes`, `tipos_ausentismo`,
  `tipos_certificado`, `parentescos`) tienen tablas, CRUD admin con permisos y
  auditoría, y son fuente para menús/validación del chat. Jornada sigue libre.
- Alias: `codigo` estable para identidad del dominio y compatibilidad; ID incremental
  para relaciones internas. Metadata histórica conserva etiquetas/snapshots.
- Review independiente: anteriores HIGH/MEDIUM resueltos, incluido snapshot retenido
  ante input inválido; no quedan BLOCKER/HIGH/MEDIUM.
- Validación: 239 tests / 1088 assertions; `git diff --check` y `pint --test --dirty`
  correctos. El check global Pint reporta 148 issues en 244 archivos, mientras los
  59 archivos modificados pasan.
- No se ejecutó migración en PostgreSQL persistente ni prueba de WhatsApp/deploy.
- Limitación: las 3 sedes sembradas son opciones heredadas, no padrón institucional.
  M5 sigue `blocked` por D2–D5 y no fue modificado.

## Daily más reciente

- [2026-09-30 — CAT-001](daily/2026-09-30.md): `done`; siguiente prioridad según
  decisión explícita del usuario, sin desbloquear M5, es validar el padrón oficial
  de sedes.

### Ejecución anterior — PROD-001

- Resultado: `done`, fase `DONE`; diagnóstico documental de preparación para
  producción, sin cambios de producto ni inspección de servidor/secretos.
- C1–C17 seguía pendiente de decisión; se distinguieron catálogos locales de
  mocks permisivos, storage metadata-only, sender nulo y configuración de tests.
- No se encontró `jsons_resultados` en archivos versionados.
- Commit de cierre: `75cc710`.

### Ejecución anterior — MA-001

- Resultado: `done`, fase `DONE`; infraestructura documental, no producto.
- Lead único escritor; auditoría y review independiente completados sin findings.
- Se incorporan workflow, memoria selectiva, contratos/ownership, handoffs y gates
  sobre la planificación existente. AGENTS y plantilla/runbook se simplifican.
- Snapshot anterior conservado íntegro en
  [archive](archive/STATUS-2026-09-27-before-multi-agent.md); las notas de M4 abierto
  o Git read-only allí son históricas, no bloqueos actuales confirmados.
- Validaciones: diff check, 18 documentos activos / 98 enlaces y anchors,
  fences y límites de memoria OK; snapshot histórico idéntico al commit base.
- Sin tests PHP ni operación Docker/Ansible: sólo Markdown. Procedimiento de
  worktrees preparado, no ensayo de implementación paralela en este corte.
- Cierre con commit documental trazable y Git limpio; evidencia en el daily.

## Estado consolidado

| Área | Estado / fuente |
| --- | --- |
| Motor y flujos | Implementación incremental; aviso familiar cerrado en [AV-001](daily/2026-09-24.md); anticipo sigue con metadata de archivos |
| Backoffice | Filament, permisos Spatie, auditoría y módulos read-only; [documentación](../docs/backoffice/README.md) |
| Storage de certificados | CERT-001 básico implementado opt-in; purga desactivada, pendiente aceptación testing y capacidad productiva; [daily](daily/2026-10-02.md) |
| Testing | Última suite de producto registrada: AV-001, 228 tests / 1058 assertions; no implica corrida en MA-001 |
| Deploy | M4 y D1/D2 cerrados según [daily 07/09](daily/2026-09-07.md); release registrado testing-2026-09-07-04, sin comprobación remota en esta sesión |
| Catálogos | CAT-001 implementado para sedes, ausentismo, certificados y parentescos; falta padrón institucional completo de sedes; [inventario](../docs/15-inventario-tablas-maestras.md) |
| Método de trabajo | MA-001 done; único agente/secuencial/paralelo según dependencias |

## Bloqueos y decisiones pendientes

- Certificados: validar CERT-001 en testing; capacidad productiva y retención
  siguen pendientes en el [plan incremental](../docs/14-certificados-problematica-y-plan-incremental.md).
- Catálogos: obtener fuente oficial y validar lista completa de sedes antes de
  sustituir las tres opciones iniciales de compatibilidad.
- Resto de hallazgos: [BACKLOG](BACKLOG.md), especialmente LOG-001, BO-003/004 y
  DEPLOY-004. Decisiones operativas/aceptación de producción en [deploy](../deploy/README.md).
- No presumir accesos remotos, permisos filesystem ni disponibilidad Docker a partir
  de sesiones anteriores; verificar sólo cuando la tarea los necesite.

## Próximo paso

Definir con el usuario la decisión de CAT-001 (en especial fuente oficial de C1,
semántica de C2 y necesidad de administración dinámica) o continuar el bloqueo
vigente de M5 D2–D5. El informe PR-002 no autoriza implementación; escoger el
siguiente milestone explícitamente antes de migrar catálogos o storage.
