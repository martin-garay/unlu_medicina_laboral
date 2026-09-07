# Despliegue de almacenamiento privado de certificados

Estado: plan pendiente de implementación y validación. Ejecución ordenada por
M5–M9 de `plan_dev/daily/2026-09-07.md`, después del cierre del deploy M4.

## Preparación

- Resolver las decisiones de BO-002 antes de habilitar el driver local.
- Reutilizar los roles de aplicación, backup y monitoring existentes, con sus
  README, defaults y tests. Los playbooks generales sólo orquestan capacidades.
- Renderizar los drivers de borrador/final y disco privado desde variables de
  inventory y el template de entorno. Mantener metadata-only como compatibilidad
  explícita; nunca presentar esos registros como binarios recuperados.
- Persistir archivos bajo el storage compartido entre releases, fuera de webroot
  y de cualquier enlace público. No almacenar binarios dentro de un release.
- Verificar propietario, grupo y permisos mínimos para PHP-FPM y CLI/scheduler;
  no resolver problemas con permisos globales de escritura.
- Guardar token de WhatsApp en Vault y evitar su exposición en diff/logs.
- Verificar desde el runtime destino DNS, TLS, proxy y acceso a la API y descarga
  de media. La conectividad de PC Uni o Composer no acredita conectividad de PHP.
- Ajustar límites y tiempos de PHP/webserver sólo si lo requiere el recorrido real;
  distinguir recepción del webhook de descarga del binario. Si M5 requiere workers,
  agregar su operación y monitoreo antes del pase.

## Respaldo y continuidad

- Incluir directorios privados y referencias de base de datos en la estrategia de
  backup existente. Comprobar coherencia DB/archivos, acceso restringido, espacio
  disponible y política de retención acordada; no habilitar purga por defecto.
- Ensayar restore con datos sintéticos en destino aislado; verificar existencia,
  hash y asociación de archivos, sin sobrescribir storage o base activos.
- Comprobar que limpieza de releases y rollback no eliminen binarios compartidos.
- Verificar compatibilidad del release anterior con la configuración de drivers:
  no alcanza con cambiar `current` si ese código no admite el driver local.
  Documentar restauración de configuración/cache y cómo impedir nuevas cargas
  durante un rollback incompatible, conservando archivos y trazabilidad.

## Validación y promoción

1. Ejecutar `bin/check-deploy` desde `deploy/provisioning`.
2. En Vagrant, validar convergencia, segunda ejecución idempotente, permisos reales,
   privacidad HTTP, persistencia entre releases, backup/restore y rollback.
3. Cerrar M4 y preparar un tag nuevo desde el commit validado, sin modificar
   `testing-2026-09-07-01` ni tags anteriores. Registrar commit y release seleccionados.
4. Desde PC Uni seguir `deployment-guide.md`: check/diff sin secretos, apply,
   health checks con curl y monitoreo; mantener `security_enabled: false` y
   `firewall_enabled: false` en testing según su decisión operativa vigente.
5. Probar WhatsApp con PDF/foto sintéticos desde menú 3 hasta anticipo registrado;
   verificar binario, hash, asociaciones y errores sin falso éxito.
6. Ejecutar segunda pasada idempotente y registrar resultados en
   `validation-matrix.md`, incluyendo release, entorno, pruebas y pendientes.

No promover a producción dentro de este corte. Si falta acceso, conectividad,
credencial o aceptación real, registrar el bloqueo sin afirmar disponibilidad.

## Criterios de aceptación

- Archivo privado durable y asociado correctamente al anticipo confirmado.
- Sin acceso público, pérdida entre releases ni secretos en evidencia operativa.
- Backup recuperable con hash comprobado y rollback compatible ensayado.
- `/up`, `/internal/chat`, aviso de ausencia y scheduler sin regresiones.
- Evidencia de WhatsApp real; tests simulados no reemplazan esta aceptación.
