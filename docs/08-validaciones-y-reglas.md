# Validaciones y reglas

## Objetivo

Este documento define cómo deben pensarse e implementarse las validaciones del sistema conversacional de Medicina Laboral UNLu, con foco en mantener la solución:

- mantenible
- extensible
- trazable
- desacoplada de los controllers
- preparada para crecer en cantidad de flujos, reglas y mensajes

## Problema a resolver

Los flujos de:

- aviso de ausencia
- anticipo de certificado médico

requieren múltiples validaciones por paso, con distintos tipos de respuestas, reintentos, cancelaciones y decisiones de negocio.

Si toda esa lógica se concentra en un único controller o en un `switch` grande, el código se vuelve difícil de mantener y frágil ante cambios.

## Principio general

Cada paso del flujo debe poder resolver, de forma desacoplada:

- qué dato espera
- cómo valida ese dato
- qué mensaje responde si el dato es válido
- qué mensaje responde si el dato es inválido
- cuántos intentos permite
- qué evento técnico registra
- a qué paso transiciona a continuación

## Objetivo de diseño

La lógica de validación debe permitir:

- agregar nuevos pasos sin romper los existentes
- agregar nuevas reglas por paso
- cambiar mensajes sin tocar lógica de negocio
- parametrizar umbrales
- reutilizar validaciones comunes
- registrar trazabilidad completa de cada error

## Tipos de validaciones

Las validaciones del sistema pueden clasificarse, al menos, en estos grupos.

## 1. Validaciones de formato

Verifican que el dato ingresado tenga una forma válida.

Ejemplos:

- campo vacío
- legajo con caracteres no numéricos
- fecha con formato inválido
- opción inexistente
- archivo con formato no permitido

## 2. Validaciones de obligatoriedad

Verifican que el dato requerido haya sido informado.

Ejemplos:

- nombre no informado
- legajo vacío
- falta de archivo adjunto
- falta de confirmación final

## 3. Validaciones de rango o dominio

Verifican que el dato esté dentro de un conjunto o rango permitido.

Ejemplos:

- sede inexistente
- tipo de ausentismo no permitido
- tipo de certificado inválido
- fecha fuera de rango
- tamaño de archivo excedido

## 4. Validaciones de consistencia entre campos

Verifican relación entre datos de un mismo flujo.

Ejemplos:

- fecha hasta menor que fecha desde
- cantidad de archivos mayor a la permitida
- certificado que no corresponde al aviso seleccionado
- tipo de ausentismo que requiere datos de familiar y no los tiene

## 5. Validaciones de negocio

Verifican reglas funcionales del dominio.

Ejemplos:

- no existe aviso previo para cargar certificado
- el aviso no está en estado válido para asociar certificado
- el plazo de carga del anticipo fue superado
- se superó el máximo de intentos permitidos
- no se permite aviso retroactivo si la regla así lo indica

## 6. Validaciones técnicas

Verifican condiciones operativas del sistema.

Ejemplos:

- no se pudo procesar el archivo
- el payload recibido no contiene mensaje interpretable
- el tipo de mensaje no es compatible con el paso actual
- error al persistir una transición o asociación

## Enfoque recomendado de implementación

Las validaciones no deben quedar mezcladas con el controller ni con el envío de mensajes.

## Separación sugerida

### Step Handler
Coordina el paso actual del flujo.

Responsabilidades:
- saber qué dato se espera
- invocar validaciones
- decidir transición
- registrar eventos
- delegar construcción de mensajes

### Validator
Evalúa si la entrada cumple las reglas del paso.

Responsabilidades:
- devolver resultado estructurado
- informar códigos de error
- no enviar mensajes directamente
- no persistir por su cuenta

### Message Resolver
Resuelve qué texto debe mostrarse según el resultado.

Responsabilidades:
- obtener mensajes desde `lang/es/*.php`
- interpolar variables si hace falta
- desacoplar validación de textos

### Step Result
Objeto de resultado que indica qué hacer después.

Puede incluir:
- válido / inválido
- código de error
- siguiente paso
- evento a registrar
- si incrementa intentos
- si corresponde cancelar o derivar

## Ejemplo conceptual

Supongamos el paso `aviso_fecha_hasta`.

El sistema debería poder hacer algo como:

1. recibir input del usuario
2. ejecutar un validador de fecha
3. ejecutar una validación de consistencia contra fecha desde
4. devolver un resultado estructurado:
   - válido
   - error `invalid_date`
   - error `before_start_date`
5. resolver el mensaje correcto
6. registrar si fue válido o inválido
7. incrementar intentos si corresponde
8. decidir si avanza, reintenta o cancela

## Códigos de error

Se recomienda que las validaciones devuelvan **códigos de error estables** y no textos finales.

Ejemplos:

- `required`
- `invalid_format`
- `invalid_option`
- `invalid_date`
- `before_start_date`
- `max_attempts_exceeded`
- `no_open_aviso`
- `certificado_deadline_exceeded`
- `invalid_attachment_type`
- `attachment_too_large`

## Ventajas

Esto permite:

- desacoplar reglas de textos
- cambiar mensajes sin tocar validadores
- reutilizar reglas en distintos pasos
- reportar métricas más consistentes
- dejar trazabilidad más clara

## Intentos

Cada paso puede tener una cantidad máxima de intentos configurable.

## Regla sugerida

- cada validación fallida puede incrementar el contador del paso actual
- al superar el umbral configurado:
  - se puede cancelar el flujo
  - se puede derivar a operador
  - se puede volver al menú
  - la acción final dependerá de la regla del flujo

## Parametrización

Los límites no deben quedar hardcodeados.

Ejemplos de parámetros a centralizar:

- máximo de intentos por paso
- máximo de intentos globales por conversación
- máximo de archivos
- tamaño máximo
- formatos permitidos
- plazo para anticipo de certificado
- palabras clave de cancelación
- umbrales de inactividad

## Ubicación sugerida

- `config/medicina_laboral.php`

## Mensajes de error y respuesta

Los textos no deben vivir en los validadores.

Se deben resolver a partir de claves de idioma.

Ubicación sugerida:

- `lang/es/whatsapp.php`

## Ejemplos de claves

- `whatsapp.errors.required`
- `whatsapp.errors.invalid_date`
- `whatsapp.errors.invalid_option`
- `whatsapp.errors.max_attempts_exceeded`
- `whatsapp.errors.no_open_aviso`

## Templates

Los mensajes largos o con interpolación compleja deben renderizarse con Blade.

Ejemplos:

- resumen de confirmación de aviso
- resumen de confirmación de anticipo
- mensaje final de aviso registrado
- mensaje final de anticipo registrado

## Reglas transversales

Estas reglas deben ser consideradas en todos los flujos.

### 1. Cancelación manual
El usuario puede cancelar en cualquier momento.

### 2. No borrar evidencia técnica
No se borran conversaciones ni mensajes.

### 3. Toda invalidación debe ser trazable
Cada error relevante debe quedar reflejado en mensajes y/o eventos.

### 4. No crear entidades de negocio con datos incompletos
El aviso o anticipo solo se crea al final del flujo.

### 5. La conversación no debe reutilizarse después de cancelarse
Un nuevo intento genera una nueva conversación.

## Validaciones por flujo

## Flujo de aviso de ausencia

Ejemplos de validaciones esperables:

- nombre obligatorio
- legajo numérico
- sede válida
- jornada laboral obligatoria
- fecha desde válida
- fecha hasta válida
- fecha hasta no menor que fecha desde
- tipo de ausentismo válido
- motivo válido
- datos de familiar completos cuando corresponda
- confirmación final explícita

## Flujo de anticipo de certificado

Ejemplos de validaciones esperables:

- identificación válida del usuario
- existencia de aviso previo
- aviso elegible para asociación
- plazo no vencido
- tipo de certificado válido
- adjunto presente
- formato permitido
- tamaño permitido
- cantidad máxima de archivos no excedida
- confirmación final explícita

## Catálogos

Los pasos que dependan de opciones predefinidas deben desacoplar el catálogo de la lógica.

Ejemplos:

- sedes
- tipos de ausentismo
- tipos de certificado
- parentescos

## Recomendación inicial

Comenzar con:

- config
- enums
- clases de catálogo simples

y evolucionar a DB solo cuando exista necesidad real de administración dinámica.

## Trazabilidad esperada

Frente a cada validación fallida debería poder saberse:

- conversación
- paso
- valor ingresado
- código de error
- mensaje enviado
- número de intento
- fecha y hora

## Métricas sugeridas

- errores por paso
- errores por tipo de validación
- pasos con más reintentos
- flujos cancelados por exceso de errores
- porcentaje de finalización por flujo

## Criterio de aceptación

La estrategia de validaciones se considerará correctamente implementada cuando el sistema permita:

- validar por paso de forma aislada
- reutilizar reglas comunes
- cambiar mensajes sin cambiar lógica
- parametrizar límites
- registrar errores de forma consistente
- agregar nuevos pasos sin degradar la mantenibilidad