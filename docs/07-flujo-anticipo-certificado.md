# Flujo de anticipo de certificado médico

## Objetivo

Este documento describe el flujo conversacional y las reglas principales para registrar un **anticipo de certificado médico** a través del chatbot de WhatsApp.

El objetivo es permitir que el usuario informe y adjunte un certificado asociado a un aviso de ausencia previo, manteniendo trazabilidad completa y validaciones extensibles.

## Principio clave

El anticipo de certificado **no es independiente** del aviso.

Para poder registrar un anticipo de certificado, previamente debe existir un **aviso de ausencia** válido, abierto o elegible para asociación, según las reglas definidas por negocio.

## Alcance actual

En esta etapa se documenta la estructura recomendada del flujo y las decisiones principales de implementación.

Puede dejarse para más adelante:

- integración real con validaciones externas
- administración dinámica del catálogo de tipos de certificado
- almacenamiento definitivo de archivos con infraestructura final
- validaciones avanzadas de plazos hábiles si requieren lógica adicional

## Punto de entrada

Desde el menú principal interesa implementar la opción:

- **3. Anticipo de certificado médico**

## Estructura general del flujo

El flujo de anticipo de certificado se compone de estas etapas:

1. Identificación del trabajador
2. Identificación del aviso previo
3. Tipo de certificado
4. Adjuntar archivo
5. Confirmación final
6. Registración efectiva del anticipo
7. Mensaje final

## Regla de diseño importante

La conversación no es el anticipo de certificado.

La conversación solo recopila y valida información.  
El anticipo se crea recién al final del flujo, cuando la información es consistente y el usuario confirma.

## Subflujo 1: identificación del trabajador

La documentación funcional indica que deben solicitarse estos datos:

- nombre completo
- número de legajo
- sede
- jornada laboral

## Recomendación

Mantener el mismo enfoque desacoplado que en el flujo de aviso:

- interfaz de identificación
- implementación mock temporal

Esto permite reutilizar comportamiento y evitar duplicar lógica.

## Subflujo 2: identificación del aviso previo

La documentación funcional indica que debe identificarse el aviso al cual quedará asociado el anticipo.

## Regla principal

Debe existir un aviso abierto, válido o elegible para asociación.

## Alternativas de implementación

### Opción A
El usuario ingresa un identificador de aviso.

### Opción B
El sistema busca avisos compatibles según el contexto del usuario y le permite seleccionar uno.

### Recomendación inicial

Para una primera implementación simple y trazable:

- permitir ingreso del identificador del aviso
- validar existencia y elegibilidad

### Implementación actual de esta etapa

En esta fase el flujo ya avanza hasta guardar transitoriamente:

- `aviso_id`
- `numero_aviso`
- `tipo_certificado`
- `tipo_certificado_label`
- `adjuntos`

La validación actual del aviso se apoya en la base local del proyecto:

- acepta `AV-{id}` o `id` numérico
- verifica que el aviso exista
- verifica legajo cuando el aviso ya lo tiene persistido
- verifica de forma simple el plazo configurado de carga

El archivo adjunto todavía no se persiste como entidad definitiva; se registra metadata mínima en la conversación para preparar la confirmación final del paso siguiente.

Más adelante puede evolucionarse a selección guiada.

## Validaciones principales

- el aviso debe existir
- el aviso debe pertenecer al trabajador correcto
- el aviso debe estar en un estado que permita asociar certificado
- no debe estar vencido si negocio así lo define
- debe respetarse el plazo permitido para registrar el anticipo

## Regla parametrizable

Debe poder configurarse el plazo permitido, por ejemplo:

- 24 horas hábiles desde la registración del aviso

Este valor no debe quedar hardcodeado.

## Subflujo 3: tipo de certificado

El sistema debe exigir la selección de un tipo de certificado válido entre opciones disponibles.

## Implementación sugerida

En la primera etapa existen dos opciones razonables:

### Opción 1
Resolverlo con config o enum

### Opción 2
Resolverlo con tabla en base de datos

## Decisión sugerida para inicio

Comenzar con:

- config o enum

y dejar documentado que puede migrarse a tabla si luego debe administrarse desde backoffice.

## Regla de diseño

La aplicación no debe asumir que este catálogo es fijo para siempre.

## Subflujo 4: adjuntar archivo

El usuario debe adjuntar uno o más archivos correspondientes al certificado.

## Parámetros configurables

Este comportamiento debe quedar parametrizado, incluyendo al menos:

- cantidad máxima de archivos
- formatos permitidos
- tamaño máximo por archivo

## Ejemplos de parámetros

- máximo 3 archivos
- formatos permitidos:
  - pdf
  - jpg
  - png
- tamaño máximo configurable

## Validaciones principales

- debe existir archivo adjunto
- el tipo de archivo debe estar permitido
- el tamaño no debe exceder el máximo
- no debe superarse la cantidad máxima de archivos
- el archivo debe poder asociarse a la conversación actual

## Consideración técnica

El sistema no debe asumir que todo mensaje recibido será texto.  
El flujo debe contemplar mensajes con adjuntos.

## Implementación actual de esta etapa

Por ahora el paso acepta un adjunto por vez y registra metadata mínima del mensaje `document` o `image`:

- `provider_media_id`
- `mime_type`
- `filename` si existe
- `caption` si existe
- `source_type`

Se validan tipos MIME permitidos desde configuración, pero no se realiza todavía descarga ni almacenamiento definitivo del archivo.

## Recomendación de trazabilidad

Registrar por cada archivo:

- nombre si existe
- tipo MIME
- tamaño
- identificador externo
- resultado de validación
- referencia técnica de almacenamiento
- asociación a conversación y anticipo

## Subflujo 5: confirmación final

Antes de registrar el anticipo, el sistema debe mostrar un resumen de la información obtenida.

## Objetivo

- confirmar el aviso asociado
- confirmar el tipo de certificado
- confirmar la cantidad de archivos recibidos
- permitir cancelar o corregir antes del alta

## Implementación sugerida

Usar template Blade para renderizar el mensaje de resumen final.

## Opciones esperadas

- confirmar
- corregir
- cancelar y volver al menú

## Subflujo 6: registración efectiva

Una vez confirmada la información, el sistema debe crear el anticipo de certificado.

## Datos mínimos sugeridos

- identificador del anticipo
- conversación de origen
- aviso asociado
- trabajador
- tipo de certificado
- referencias a archivos adjuntos
- estado inicial
- timestamps de creación

## Estado inicial sugerido

Dependiendo del modelo operativo futuro, podría considerarse:

- `pendiente`
- `registrado`
- `a_validar`
- `vinculado_pendiente_revision`

La definición final deberá alinearse con el módulo administrativo posterior.

## Subflujo 7: mensaje final

Una vez registrado el anticipo, el sistema debe informar al usuario que la carga fue realizada.

## Recomendación

El mensaje final debería contener:

- confirmación de registración
- número o identificador del anticipo
- aviso asociado
- información adicional definida por negocio

## Validaciones y errores

Este flujo también debe diseñarse de forma extensible.

Cada paso debe definir:

- dato esperado
- reglas de validación
- mensajes de error
- cantidad de intentos
- transición al siguiente paso

## Reglas especiales mencionadas

La documentación funcional indica dos reglas relevantes:

1. Debe estar dentro del plazo de 24 horas hábiles desde el registro del aviso.
2. En caso de detectarse un dato no válido, el sistema debe impedir el registro y solicitar corrección, con hasta 3 intentos.

## Recomendación de implementación

Ambas reglas deben parametrizarse en:

- `config/medicina_laboral.php`

Nunca hardcodearlas en controllers.

## Cancelación manual

El usuario podrá cancelar el flujo en cualquier momento.

Cancelar implica:

- cerrar la conversación
- conservar mensajes y eventos
- no crear anticipo
- no reutilizar la conversación cancelada

Si el usuario reinicia luego, debe abrirse una nueva conversación.

## Inactividad

El flujo debe soportar:

- recordatorio automático
- segundo umbral de inactividad
- cancelación automática
- registro técnico de eventos

La implementación operativa de esto debe resolverse con:

- Laravel Scheduler

## Trazabilidad

Todo el proceso debe permitir responder preguntas como:

- qué conversación generó el anticipo
- a qué aviso quedó asociado
- cuántos mensajes necesitó el usuario
- qué errores ocurrieron
- si hubo archivos inválidos
- si el flujo fue cancelado o expiró

## Métricas sugeridas

- cantidad de mensajes del flujo
- cantidad de mensajes válidos e inválidos
- cantidad de archivos rechazados
- tiempo hasta registración efectiva
- cantidad de anticipos fallidos por falta de aviso válido
- cantidad de cancelaciones por paso

## Criterio de aceptación de implementación

Se considerará correctamente implementado este flujo cuando el sistema pueda:

- iniciar desde menú principal
- validar identificación del trabajador
- exigir aviso previo
- validar elegibilidad del aviso
- solicitar tipo de certificado
- recibir y validar adjuntos
- mostrar resumen final
- registrar el anticipo solo tras confirmación
- asociar el anticipo a la conversación correcta
- dejar trazabilidad completa
