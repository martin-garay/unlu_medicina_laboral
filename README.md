# Medicina Laboral WhatsApp MVP

Proyecto Laravel + PostgreSQL que implementa un chatbot básico usando la WhatsApp Cloud API para registrar avisos de inasistencia y certificados médicos.

## Requisitos
- PHP 8.2+
- Composer
- PostgreSQL 13+

## Configuración rápida
1. Clonar el repo y duplicar el archivo de entorno:
   ```bash
   cp .env.example .env
   ```
2. Ajustar las variables en `.env`:
   - `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`: conexión PostgreSQL.
   - `WHATSAPP_TOKEN`: token de acceso de WhatsApp Cloud API.
   - `WHATSAPP_PHONE_ID`: phone number ID del sandbox/instancia.
   - `WHATSAPP_VERIFY_TOKEN`: token privado para validar el webhook.
3. Instalar dependencias:
   ```bash
   composer install
   ```
4. Generar la APP_KEY y migrar:
   ```bash
   php artisan key:generate
   php artisan migrate
   ```
5. Levantar el servidor local:
   ```bash
   php artisan serve
   ```

> Nota: el archivo `.env` (y cualquier variación `.env.*`) ya está excluido del repositorio. Usa `.env.example` como base y no subas tus credenciales a git.

## Webhook en Meta (alto nivel)
- URL de verificación y recepción: `https://tu-dominio.com/api/whatsapp/webhook`.
- Método: GET para verificación (usa `hub.mode`, `hub.verify_token`, `hub.challenge`).
- Método: POST para eventos entrantes.
- Suscribir el phone number al producto de WhatsApp en Meta Developer.

## Flujo conversacional soportado
1. Bot solicita DNI.
2. Pregunta si registrar "inasistencia" o "certificado".
3. `inasistencia` → pide cantidad de días y registra aviso con fecha de inicio = hoy.
4. `certificado` → guarda el texto enviado como certificado.
5. Responde confirmación y cierra la conversación.

## Archivos clave
- `routes/api.php`: Webhook GET/POST.
- `app/Http/Controllers/WhatsappWebhookController.php`: lógica del chatbot y envío de respuestas.
- `app/Models/Aviso.php` y `app/Models/Conversacion.php`: modelos Eloquent.
- `database/migrations/*create_avisos*_table.php`: migración de avisos.
- `database/migrations/*create_conversaciones*_table.php`: migración de conversaciones.
