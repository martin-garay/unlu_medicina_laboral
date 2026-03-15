# Flujo de aviso de ausencia

## Objetivo

Este documento describe el flujo conversacional y las reglas principales para registrar un **aviso de ausencia** a través del chatbot de WhatsApp.

El objetivo del flujo es guiar al usuario paso a paso, validar la información mínima requerida, registrar trazabilidad completa de la interacción y materializar un **aviso** solo cuando se cumplan las condiciones necesarias.

## Alcance actual

En esta etapa se documenta el flujo conversacional y la forma recomendada de implementarlo.

Quedan fuera de esta primera implementación completa:

- integración real con sistemas externos para identificar al trabajador
- envío real de email
- administración dinámica de catálogos desde backoffice
- reglas avanzadas que requieran validación contra sistemas externos no disponibles todavía

## Punto de partida

Según la documentación funcional, el menú principal contempla:

1. Consultas
2. Aviso de ausencia
3. Anticipo de certificado médico

En el alcance actual interesa implementar primero la opción:

- **2. Aviso de ausencia**

## Precondiciones

Para iniciar el flujo:

- el usuario debe tener una conversación activa o se debe crear una nueva
- el sistema debe registrar todos los mensajes entrantes y salientes
- el usuario debe poder cancelar el flujo en cualquier momento
- la conversación debe quedar trazada aunque no termine exitosamente

## Estructura general del flujo

El flujo de aviso de ausencia se compone de estas grandes etapas:

1. Identificación del trabajador
2. Período de ausentismo
3. Tipo de ausentismo
4. Motivo
5. Domicilio circunstancial
6. Observaciones adicionales
7. Confirmación final
8. Registración efectiva del aviso
9. Mensaje final

## Regla de diseño importante

La conversación no es el aviso.

La conversación solo recopila, valida y estructura los datos.  
El **aviso** se crea recién al final del flujo, luego de la confirmación final y de pasar las validaciones correspondientes.

## Subflujo 1: identificación del trabajador

La documentación funcional indica que deben solicitarse los siguientes datos:

- nombre completo
- número de legajo
- sede
- jornada laboral

## Implementación recomendada en esta etapa

Por ahora se puede dejar desacoplado el mecanismo de identificación real mediante una interfaz o servicio mock.

Ejemplo conceptual:

- `WorkerIdentificationService`
- `MockWorkerIdentificationService`

Esto permite avanzar con el flujo sin bloquear el desarrollo por integraciones externas.

## Pasos sugeridos

### 1. Nombre completo
Dato esperado:
- texto libre no vacío

Validaciones mínimas sugeridas:
- obligatorio
- longitud mínima configurable

### 2. Número de legajo
Dato esperado:
- solo números

Validaciones mínimas sugeridas:
- obligatorio
- solo caracteres numéricos
- longitud mínima/máxima configurable si aplica

### 3. Sede
Dato esperado:
- selección entre opciones válidas

Implementación inicial sugerida:
- catálogo en archivo de configuración

### 4. Jornada laboral
Dato esperado:
- texto libre

Validaciones mínimas sugeridas:
- obligatorio
- sin validación fuerte en esta primera etapa

## Subflujo 2: período de ausentismo

El sistema debe solicitar el período de ausencia.

## Pasos sugeridos

### 5. Fecha desde
Dato esperado:
- fecha válida

### 6. Fecha hasta
Dato esperado:
- fecha válida

## Validaciones principales

- la fecha hasta no puede ser menor a la fecha desde
- no se deben aceptar fechas inválidas
- si el documento funcional lo exige, no se deben aceptar avisos retroactivos
- la parametrización de formatos de fecha debe definirse de forma centralizada

## Subflujo 3: tipo de ausentismo

El sistema debe permitir seleccionar un tipo de ausentismo válido.

## Implementación sugerida

Inicialmente el catálogo de tipos puede vivir en:

- `config/medicina_laboral.php`

Más adelante podrá migrarse a base de datos si la administración de opciones lo requiere.

## Caso especial: familiar enfermo

La documentación indica que, según el tipo de ausentismo, puede ser necesario completar información adicional del familiar.

Esto implica un subflujo condicional.

### Datos posibles
- nombre del familiar
- parentesco
- otros datos que se definan con el cliente

## Recomendación de implementación

No mezclar esta lógica condicional directamente en el controller.  
Resolverla como pasos adicionales activados según el valor de `tipo_ausentismo`.

## Subflujo 4: motivo

Se debe solicitar el motivo del aviso.

Dependiendo del alcance funcional final, esto puede ser:

- texto libre
- selección desde catálogo
- modelo híbrido

En la primera etapa, se recomienda comenzar con:

- catálogo para tipos estructurados
- texto libre adicional si hiciera falta

## Subflujo 5: domicilio circunstancial

El sistema debe solicitar o permitir informar domicilio circunstancial.

## Recomendación

Definir explícitamente si es:

- obligatorio
- opcional
- condicional según el tipo de ausentismo

En el diseño actual conviene soportar el dato como opcional y registrar si fue omitido por decisión del usuario.

## Subflujo 6: observaciones adicionales

El sistema debe permitir que el usuario agregue observaciones complementarias.

## Recomendación

Tratar este paso como:

- opcional
- texto libre
- con longitud máxima configurable

## Subflujo 7: confirmación final

Antes de crear el aviso, el sistema debe mostrar un resumen de la información recopilada y pedir confirmación.

## Objetivo

- dar visibilidad al usuario sobre lo ingresado
- permitir corrección o cancelación antes del alta
- evitar crear avisos con errores de carga

## Implementación sugerida

Utilizar un template Blade para construir el resumen final.

Ejemplo de contenido esperado:

- nombre
- legajo
- sede
- jornada
- período
- tipo de ausentismo
- motivo
- domicilio
- observaciones

## Opciones esperadas

- confirmar
- corregir
- cancelar y volver al menú

## Subflujo 8: registración efectiva

Solo luego de la confirmación final el sistema debe crear el aviso.

## Datos mínimos del aviso

La definición exacta dependerá del modelo de datos final, pero conceptualmente debería persistirse:

- identificador del aviso
- conversación de origen
- teléfono
- datos del trabajador
- período
- tipo de ausentismo
- motivo
- domicilio circunstancial
- observaciones
- estado inicial
- timestamps de creación

## Estado inicial sugerido

Dado el alcance del proyecto, conviene considerar que el aviso se genere con un estado inicial controlado, por ejemplo:

- `pendiente`
- `registrado`
- `a_validar`

La decisión final dependerá del diseño del módulo operativo posterior.

## Subflujo 9: mensaje final

Una vez registrado el aviso, el sistema debe informar al usuario que el proceso fue completado.

## Recomendación

El mensaje final debería renderizarse con Blade y contener al menos:

- confirmación de registración
- número o identificador del aviso
- recordatorio sobre el anticipo de certificado si corresponde
- información adicional definida por negocio

## Validaciones

Este flujo tendrá muchas validaciones y respuestas de error. Para mantenerlo extensible se recomienda que cada paso defina:

- dato esperado
- reglas de validación
- mensajes de error
- cantidad de intentos
- transición al siguiente paso

## Ejemplos de validaciones posibles

- valor obligatorio
- opción inválida
- legajo no numérico
- fecha inválida
- fecha hasta menor a fecha desde
- período fuera de rango
- superación del máximo de intentos

## Cancelación manual

El usuario puede cancelar en cualquier momento.

## Regla acordada

Cancelar significa:

- cerrar la conversación actual
- conservar todos los mensajes y eventos
- no crear aviso
- no reutilizar esa conversación para un nuevo intento

Si el usuario vuelve a iniciar el proceso, debe abrirse una nueva conversación.

## Inactividad

Si el usuario abandona el flujo, deben aplicarse recordatorios y cancelación automática según parámetros configurables.

La lógica operativa de este comportamiento se documentará en:

- `docs/09-scheduler-e-inactividad.md`

## Trazabilidad

Todo el flujo debe dejar trazabilidad suficiente para responder, entre otras, estas preguntas:

- cuántos mensajes necesitó el usuario para completar el aviso
- en qué paso hubo errores
- qué mensajes fueron inválidos
- en qué momento se canceló o expiró el flujo
- qué conversación generó efectivamente el aviso

## Métricas sugeridas

- cantidad total de mensajes del flujo
- cantidad de mensajes válidos
- cantidad de mensajes inválidos
- tiempo total hasta registración efectiva
- paso con mayor fricción
- cantidad de cancelaciones por paso

## Criterio de aceptación de implementación

Se considerará correctamente implementado este flujo cuando el sistema pueda:

- iniciar el flujo desde menú principal
- recopilar todos los datos requeridos
- validar paso a paso
- permitir cancelación
- soportar inactividad
- mostrar resumen final
- crear el aviso solo al confirmar
- asociar el aviso a la conversación correcta
- dejar trazabilidad completa