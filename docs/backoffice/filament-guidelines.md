# Guía de Filament

## Propósito

Filament se usará como capa administrativa para operar el sistema. Debe resolver navegación, formularios, tablas, filtros, acciones visuales, dashboards y RelationManagers.

La lógica de negocio debe vivir fuera de Filament.

## Resources esperados

Los Resources iniciales probables son:

- usuarios administrativos
- roles y permisos
- avisos de ausencia
- anticipos/certificados médicos
- conversaciones
- mensajes de conversación
- auditoría administrativa
- reportes o exportaciones, si se modelan como entidad

El Resource de anticipos/certificados puede mostrarse al operador como "Certificados médicos", pero debe quedar claro que el modelo Eloquent vigente es `AnticipoCertificado`.

## Forms

Los formularios de Filament pueden validar formato, campos requeridos y restricciones simples de UI.

Las reglas de negocio deben validarse en backend mediante Application Actions y Domain Services. No confiar únicamente en validaciones de formulario.

## Tables y filtros

Las tablas deben priorizar operación y trazabilidad:

- búsqueda por número, legajo, nombre y fechas
- filtros por estado
- filtros por sede o tipo cuando corresponda
- badges para estados
- columnas compactas y legibles
- links a relaciones relevantes

## Filament Actions

Las Filament Actions son botones o interacciones visuales.

Ejemplos:

- verificar certificado
- observar certificado
- invalidar certificado
- cancelar aviso
- asociar aviso
- desasociar aviso
- descargar archivo
- generar reporte

Cada Filament Action relevante debe delegar en una Application Action.

```text
Filament Action: botón "Invalidar certificado"
    ↓
Application Action: InvalidarCertificadoAction
```

## RelationManagers

La relación N a N aviso-certificado debe exponerse con RelationManagers.

En el Resource de avisos:

- mostrar anticipos/certificados asociados por `Aviso::anticiposCertificado()`
- permitir navegar al detalle del certificado
- mostrar metadata de pivot: `origen`, `estado_vinculo`, timestamps

En el Resource de anticipos/certificados:

- mostrar avisos asociados por `AnticipoCertificado::avisos()`
- permitir navegar al detalle del aviso
- mostrar metadata de pivot

Si se habilita asociación manual, debe pasar por `AsociarCertificadoAAvisoAction`. Si se habilita desasociación, debe pasar por `DesasociarCertificadoDeAvisoAction`.

## Permisos visibles

Los botones, páginas y acciones deben ocultarse o deshabilitarse según permisos del operador.

La validación visual no reemplaza la validación backend. La Application Action también debe verificar permisos o recibir una decisión ya validada por una capa autorizada.

## Manejo de errores

Filament debe traducir excepciones de dominio a notificaciones de operador.

Ejemplos:

- certificado no verificable por estado actual
- aviso no elegible para asociación
- operador sin permiso suficiente
- archivo sensible no disponible

No mostrar stack traces ni mensajes técnicos en UI administrativa.

## Dashboards y widgets

Los widgets deben consultar datos agregados o servicios de reporte. Si una consulta crece o se vuelve costosa, moverla a un servicio y considerar job o materialización.

El dashboard inicial debería priorizar:

- avisos por estado
- certificados por estado
- certificados pendientes de revisión
- conversaciones con errores o expiradas
- actividad reciente administrativa

## Performance

Las exportaciones pesadas no deben bloquear la UI. Deben planificarse con Queued Jobs cuando el volumen lo exija.

Los reportes simples pueden ser síncronos si son rápidos y medibles.

Las descargas y exportaciones con datos sensibles deben auditarse.
