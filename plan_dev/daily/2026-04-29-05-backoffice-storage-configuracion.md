# Daily Plan
## Fecha
2026-04-29

## Nombre
Backoffice storage and configuration

## Objetivo del dia
Dejar separado el frente futuro de storage privado, visualización segura de archivos y configuración administrativa.

## Estado actual resumido

- `I4 - Storage privado de certificados` está pendiente.
- No se debe implementar todavía descarga ni visualización real de archivos médicos.
- Existen servicios metadata-only de storage como etapa previa.
- Configuración administrativa todavía no tiene UI.

## Reglas de ejecucion

- No iniciar este daily hasta cerrar módulos read-only base.
- No exponer archivos médicos públicamente.
- No implementar descarga/preview sin decisión de storage privado.
- Toda lectura futura de archivos médicos debe auditarse.

---

## P0
### ID
P0

### Nombre
Abrir daily futuro de storage/configuración

### Objetivo
Reservar el frente para cuando haya decisión de storage privado.

### Estado
`pending`

---

## P1
### ID
P1

### Nombre
Definir estrategia I4 de storage privado

### Objetivo
Resolver diseño de almacenamiento y acceso seguro a certificados.

### Estado
`blocked`

### Bloqueo
- requiere decisión humana sobre storage privado, retención, permisos y auditoría de lectura.

---

## P2
### ID
P2

### Nombre
Implementar descarga/visualización segura de archivos

### Objetivo
Agregar acceso controlado a archivos médicos después de I4.

### Estado
`blocked`

### Bloqueo
- depende de P1.

---

## P3
### ID
P3

### Nombre
Planificar configuración administrativa

### Objetivo
Definir qué parámetros del sistema se administrarán desde UI y cuáles seguirán en `config/*.php`.

### Estado
`pending`

