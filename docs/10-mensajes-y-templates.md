# Mensajes y templates

## Objetivo

Este documento define cómo deben organizarse los mensajes del sistema conversacional de Medicina Laboral UNLu para que sean:

- mantenibles
- reutilizables
- parametrizables
- editables a futuro
- desacoplados de la lógica de negocio

## Problema a resolver

Los flujos del sistema requieren muchos mensajes distintos, por ejemplo:

- menú principal
- solicitud de datos
- validaciones fallidas
- reintentos
- confirmaciones
- recordatorios por inactividad
- cancelaciones
- mensajes finales de aviso
- mensajes finales de anticipo de certificado

Si estos textos quedan hardcodeados en controllers o services:

- el código se vuelve difícil de mantener
- se mezclan lógica y contenido
- es más difícil cambiar textos
- se complica una futura administración desde backoffice

## Regla principal

Los textos no deben quedar hardcodeados en controllers, services o handlers.

## Estrategia recomendada

Separar los mensajes en dos grupos:

### 1. Mensajes cortos o estructurados
Deben vivir en archivos de idioma de Laravel.

Ubicación sugerida:

- `lang/es/whatsapp.php`

### 2. Mensajes largos o con estructura variable
Deben renderizarse mediante templates Blade.

Ubicación sugerida:

- `resources/views/messages/...`

## Qué tipo de mensajes van en `lang/es/whatsapp.php`

Este archivo debería contener claves estables para mensajes como:

- menú principal
- opciones de menú
- solicitud de cada dato
- mensajes de error por validación
- mensajes de reintento
- mensajes de cancelación
- mensajes de inactividad
- textos breves de confirmación

## Ejemplos de categorías sugeridas

- `menu`
- `identificacion`
- `aviso`
- `certificado`
- `errores`
- `timeouts`
- `confirmaciones`
- `cancelacion`

## Ejemplo conceptual

```php
return [
    'menu' => [
        'principal' => '¿Qué desea hacer?',
        'opciones' => "1. Consultas\n2. Aviso de ausencia\n3. Anticipo de certificado médico",
    ],
    'identificacion' => [
        'nombre' => 'Por favor, indique su nombre completo.',
        'legajo' => 'Ingrese su número de legajo (solo números).',
        'sede' => 'Seleccione una sede válida.',
        'jornada' => 'Indique su jornada laboral.',
    ],
    'errores' => [
        'required' => 'El dato ingresado es obligatorio.',
        'invalid_option' => 'La opción ingresada no es válida.',
        'invalid_date' => 'La fecha ingresada no es válida.',
        'max_attempts_exceeded' => 'Se alcanzó el máximo de intentos permitidos.',
    ],
];
```

## Implementación inicial actual

En la primera centralización del proyecto conviene dejar al menos:

- `lang/es/whatsapp.php`
- `config/medicina_laboral.php`
- `resources/views/messages/aviso/...`
- `resources/views/messages/certificado/...`
- `resources/views/messages/comunes/...`

Los textos cortos deben resolverse con `__('whatsapp...')`.

Los parámetros y catálogos deben resolverse con `config('medicina_laboral...')`.

Los mensajes largos o institucionales deben renderizarse con Blade, por ejemplo:

```php
view('messages.aviso.confirmacion_final', $data)->render();
```

## Nota de nomenclatura transitoria

Si se necesitan claves temporales para sostener el flujo actual mientras se refactoriza el webhook, la convención recomendada es usar sufijos como `transicional`.

Evitar `legacy` para mensajes del chatbot, porque describe peor la intención y deja una deuda menos clara.

Ejemplos preferidos:

- `whatsapp.aviso.prompts.cantidad_dias_transicional`
- `whatsapp.certificado.detalle_o_adjunto_transicional`

Si hoy todavía existen claves con `legacy`, deben renombrarse en el siguiente ajuste menor para mantener la base consistente.
