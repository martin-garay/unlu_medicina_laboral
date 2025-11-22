# Sistema de avisos de medicina laboral (Laravel + PostgreSQL + WhatsApp Cloud API)

Esta guía en español describe los pasos para crear un MVP completo en Laravel que reciba avisos de medicina laboral a través de WhatsApp Cloud API, persista los datos en PostgreSQL y mantenga el estado de la conversación en una tabla `conversaciones`.

## 1. Creación del proyecto

### Comandos iniciales

```bash
# 1) Crear el proyecto Laravel (usa la última versión estable)
composer create-project laravel/laravel medicina-laboral

cd medicina-laboral

# 2) Instalar dependencias útiles (la API HTTP de Laravel ya viene incluida)
composer install

# 3) Opcional: instalar laravel/ide-helper para autocompletado (solo en local)
# composer require --dev barryvdh/laravel-ide-helper
```

### Variables de entorno (`.env`)

Ejemplo de configuración mínima:

```
APP_NAME="MedicinaLaboral"
APP_ENV=local
APP_KEY=base64:GENERAR_CON_PHP_ARTISAN_KEY:GENERATE
APP_DEBUG=true
APP_URL=http://localhost

# Base de datos PostgreSQL
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=medicina_laboral
DB_USERNAME=postgres
DB_PASSWORD=postgres

# WhatsApp Cloud API
WHATSAPP_TOKEN=token_de_acceso_largo_de_facebook         # Token de acceso permanente (Bearer) para la app de Meta
WHATSAPP_PHONE_ID=123456789012345                        # ID del teléfono de WhatsApp Cloud (de la app Meta)
WHATSAPP_VERIFY_TOKEN=verificacion_local_de_webhook      # Token que definís para validar el webhook
```

- `WHATSAPP_TOKEN`: token de acceso (Bearer) generado en la app de Meta para enviar mensajes.
- `WHATSAPP_PHONE_ID`: identificador del número de teléfono de WhatsApp Cloud (lo entrega Meta).
- `WHATSAPP_VERIFY_TOKEN`: cadena que definís y luego cargas en la consola de Meta para verificar el webhook.

> Usaremos `routes/api.php` para el webhook (`/api/whatsapp/webhook`).

## 2. Migraciones y modelos

Crear las migraciones:

```bash
php artisan make:migration create_avisos_table
php artisan make:migration create_conversaciones_table
```

### Migración `database/migrations/xxxx_xx_xx_xxxxxx_create_avisos_table.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('avisos', function (Blueprint $table) {
            $table->id();
            $table->string('dni');
            $table->string('tipo'); // inasistencia | certificado
            $table->date('fecha_inicio')->nullable();
            $table->integer('cantidad_dias')->nullable();
            $table->text('certificado_base64')->nullable();
            $table->string('wa_number');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('avisos');
    }
};
```

### Migración `database/migrations/xxxx_xx_xx_xxxxxx_create_conversaciones_table.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('conversaciones', function (Blueprint $table) {
            $table->id();
            $table->string('wa_number')->unique();
            $table->string('estado'); // esperando_dni, esperando_tipo, etc.
            $table->string('dni')->nullable();
            $table->string('tipo')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conversaciones');
    }
};
```

### Modelos

```bash
php artisan make:model Aviso
php artisan make:model Conversacion
```

#### `app/Models/Aviso.php`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Aviso extends Model
{
    use HasFactory;

    protected $fillable = [
        'dni',
        'tipo',
        'fecha_inicio',
        'cantidad_dias',
        'certificado_base64',
        'wa_number',
    ];
}
```

#### `app/Models/Conversacion.php`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Conversacion extends Model
{
    use HasFactory;

    protected $fillable = [
        'wa_number',
        'estado',
        'dni',
        'tipo',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];
}
```

## 3. Rutas

Archivo `routes/api.php`:

```php
<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\WhatsappWebhookController;

Route::get('/whatsapp/webhook', [WhatsappWebhookController::class, 'verify']);
Route::post('/whatsapp/webhook', [WhatsappWebhookController::class, 'receive']);
```

## 4. Controlador `WhatsappWebhookController`

Crear el controlador:

```bash
php artisan make:controller WhatsappWebhookController
```

Contenido sugerido en `app/Http/Controllers/WhatsappWebhookController.php`:

```php
<?php

namespace App\Http\Controllers;

use App\Models\Aviso;
use App\Models\Conversacion;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsappWebhookController extends Controller
{
    /**
     * Verifica el webhook con el token configurado en Meta.
     */
    public function verify(Request $request)
    {
        $mode = $request->query('hub_mode');
        $token = $request->query('hub_verify_token');
        $challenge = $request->query('hub_challenge');

        // Meta envía hub.mode y hub.verify_token; si coinciden, devolvemos el challenge
        if ($mode === 'subscribe' && $token === config('app.whatsapp_verify_token', env('WHATSAPP_VERIFY_TOKEN'))) {
            return response($challenge, 200);
        }

        return response('Invalid verify token', 403);
    }

    /**
     * Recibe mensajes entrantes desde el webhook de WhatsApp Cloud API.
     */
    public function receive(Request $request)
    {
        $payload = $request->all();
        Log::info('Webhook recibido', $payload);

        // Extraer el número y el cuerpo del mensaje de texto (si existe)
        $entry = $payload['entry'][0] ?? null;
        $changes = $entry['changes'][0] ?? null;
        $message = $changes['value']['messages'][0] ?? null;

        if (!$message) {
            return response()->json(['status' => 'no_message'], 200);
        }

        $waNumber = $message['from']; // número del remitente
        $text = $message['text']['body'] ?? '';

        // Buscar o crear una conversación activa por número de WhatsApp
        $conversation = Conversacion::firstOrCreate(
            ['wa_number' => $waNumber],
            ['estado' => 'esperando_dni']
        );

        // Respuesta inicial si recién se creó
        if ($conversation->wasRecentlyCreated) {
            $this->sendText($waNumber, 'Por favor, escribí tu DNI.');
            return response()->json(['status' => 'waiting_dni'], 200);
        }

        // Flujo de conversación según el estado
        switch ($conversation->estado) {
            case 'esperando_dni':
                $conversation->dni = $text;
                $conversation->estado = 'esperando_tipo';
                $conversation->save();
                $this->sendText($waNumber, '¿Querés notificar una "inasistencia" o subir un "certificado"?');
                break;

            case 'esperando_tipo':
                $lower = strtolower($text);
                if (str_contains($lower, 'inasistencia')) {
                    $conversation->tipo = 'inasistencia';
                    $conversation->estado = 'esperando_cantidad_dias';
                    $conversation->save();
                    $this->sendText($waNumber, '¿Cuántos días de inasistencia querés registrar?');
                } elseif (str_contains($lower, 'certificado')) {
                    $conversation->tipo = 'certificado';
                    $conversation->estado = 'esperando_certificado';
                    $conversation->save();
                    $this->sendText($waNumber, 'Podés escribir un breve detalle del certificado o adjuntar una imagen (por ahora solo manejamos texto).');
                } else {
                    $this->sendText($waNumber, 'No entendí. Respondé "inasistencia" o "certificado".');
                }
                break;

            case 'esperando_cantidad_dias':
                $cantidadDias = (int) $text;

                Aviso::create([
                    'dni' => $conversation->dni,
                    'tipo' => 'inasistencia',
                    'fecha_inicio' => Carbon::now()->toDateString(),
                    'cantidad_dias' => $cantidadDias,
                    'wa_number' => $waNumber,
                ]);

                $conversation->delete();
                $this->sendText($waNumber, '✅ Inasistencia registrada. ¡Que te mejores!');
                break;

            case 'esperando_certificado':
                // En futuras versiones se puede procesar multimedia aquí (imágenes, documentos, etc.)
                Aviso::create([
                    'dni' => $conversation->dni,
                    'tipo' => 'certificado',
                    'certificado_base64' => $text,
                    'wa_number' => $waNumber,
                ]);

                $conversation->delete();
                $this->sendText($waNumber, '✅ Certificado registrado. ¡Gracias por avisar!');
                break;

            default:
                // Estado inesperado: reiniciamos conversación
                $conversation->estado = 'esperando_dni';
                $conversation->dni = null;
                $conversation->tipo = null;
                $conversation->metadata = null;
                $conversation->save();
                $this->sendText($waNumber, 'Reiniciamos el flujo. Por favor, escribí tu DNI.');
                break;
        }

        return response()->json(['status' => 'ok'], 200);
    }

    /**
     * Envía un mensaje de texto usando WhatsApp Cloud API.
     */
    private function sendText(string $to, string $message): void
    {
        $token = env('WHATSAPP_TOKEN');
        $phoneId = env('WHATSAPP_PHONE_ID');

        // Endpoint de WhatsApp Cloud API (reemplazar v20.0 por la versión vigente si cambia)
        $url = "https://graph.facebook.com/v20.0/{$phoneId}/messages";

        Http::withToken($token)
            ->post($url, [
                'messaging_product' => 'whatsapp',
                'to' => $to,
                'type' => 'text',
                'text' => [
                    'preview_url' => false,
                    'body' => $message,
                ],
            ]);
    }
}
```

## 5. Flujo de conversación

- **Inicio**: si no hay conversación para el número → crear `conversacion` con `estado = esperando_dni` y responder "Por favor, escribí tu DNI.".
- **esperando_dni**: guardar DNI, pasar a `esperando_tipo`, responder "¿Querés notificar una \"inasistencia\" o subir un \"certificado\"?".
- **esperando_tipo**:
  - Si contiene "inasistencia" → `estado = esperando_cantidad_dias`, responder "¿Cuántos días de inasistencia querés registrar?".
  - Si contiene "certificado" → `estado = esperando_certificado`, responder con instrucciones de certificado.
- **esperando_cantidad_dias**: guardar aviso con `fecha_inicio = hoy`, `tipo = inasistencia`, `cantidad_dias = (int) mensaje`, cerrar conversación, responder confirmación.
- **esperando_certificado**: guardar aviso `tipo = certificado` con el texto recibido en `certificado_base64`, cerrar conversación, responder confirmación.

## 6. README.md

README inicial recomendado para el proyecto real (copiar en `README.md` dentro del proyecto Laravel):

```markdown
# Sistema de avisos de medicina laboral (Laravel + PostgreSQL + WhatsApp Cloud API)

## Objetivo
MVP para registrar avisos de inasistencia y certificados médicos enviados por empleados vía WhatsApp. El sistema guarda los datos en PostgreSQL y mantiene el estado del chat para cada número.

## Requisitos
- PHP 8.2+
- Composer
- PostgreSQL 13+
- Cuenta en Meta con WhatsApp Cloud API habilitada

## Pasos para levantar en local
1. Clonar el repo y entrar a la carpeta.
2. `composer install`
3. Copiar `.env.example` a `.env` y completar variables (DB + WhatsApp).
4. `php artisan key:generate`
5. `php artisan migrate`
6. `php artisan serve`

## Configuración de webhook en Meta (alto nivel)
- En la app de Meta, sección **Webhook**, configurar la URL pública: `https://TU_DOMINIO/api/whatsapp/webhook`.
- Definir el **Verify Token** con el mismo valor que `WHATSAPP_VERIFY_TOKEN`.
- Suscribir el recurso **messages** en el objeto **whatsapp_business_account**.

## Ejemplo de flujo conversacional
1. Usuario: "Hola"
2. Bot: "Por favor, escribí tu DNI."
3. Usuario: "12345678"
4. Bot: "¿Querés notificar una \"inasistencia\" o subir un \"certificado\"?"
5. Usuario: "inasistencia"
6. Bot: "¿Cuántos días de inasistencia querés registrar?"
7. Usuario: "2"
8. Bot: "✅ Inasistencia registrada. ¡Que te mejores!"

### Certificado
1. Usuario: "Hola"
2. Bot: "Por favor, escribí tu DNI."
3. Usuario: "87654321"
4. Bot: "¿Querés notificar una \"inasistencia\" o subir un \"certificado\"?"
5. Usuario: "certificado"
6. Bot: "Podés escribir un breve detalle del certificado o adjuntar una imagen (por ahora solo manejamos texto)."
7. Usuario: "Tengo certificado por gripe"
8. Bot: "✅ Certificado registrado. ¡Gracias por avisar!"
```

Con estos pasos y archivos, copiando el código en las rutas, controladores, modelos y migraciones indicadas, se obtiene un MVP funcional para recibir avisos vía WhatsApp y persistirlos en PostgreSQL.
