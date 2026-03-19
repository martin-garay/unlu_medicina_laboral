# Medicina Laboral WhatsApp MVP

Proyecto Laravel + PostgreSQL dockerizado para un chatbot básico usando la WhatsApp Cloud API. Todo se ejecuta dentro de contenedores: no necesitas instalar PHP, Composer ni PostgreSQL en tu máquina.

## Requisitos
- Docker
- Docker Compose

## Puesta en marcha rápida
1. Copiar el entorno base para Docker:
   ```bash
   cp .env.docker.example .env
   ```
2. Levantar los contenedores (build inicial incluido):
   ```bash
   docker compose up -d --build
   ```
3. Instalar dependencias PHP sin instalar Composer localmente:
   ```bash
   docker compose run --rm composer install
   ```
4. Generar la APP_KEY dentro del contenedor de la app:
   ```bash
   docker compose exec app php artisan key:generate
   ```
5. Ejecutar migraciones en PostgreSQL dentro de Docker:
   ```bash
   docker compose exec app php artisan migrate
   ```
6. Ver logs de Laravel en vivo (con `LOG_CHANNEL=stderr` para enviarlos al stdout/stderr del contenedor):
   ```bash
   docker compose logs -f app
   ```
7. Bajar todo cuando termines:
   ```bash
   docker compose down
   ```

> Nota: los servicios usan usuario no-root con tu UID/GID para evitar archivos con permisos de root en el host.

## Servicios en `docker-compose.yml`
- `app`: PHP 8.3 CLI con extensiones de PostgreSQL y Laravel; monta el repo en `/var/www/html` y expone `8000`.
- `db`: PostgreSQL 16 con volumen persistente `db_data`.
- `composer`: imagen oficial `composer:2` para correr comandos sin instalar Composer localmente.

## Variables de entorno
Usa `.env.docker.example` como plantilla. Valores claves:
- `DB_HOST=db` y `DB_PORT=5432` para hablar con el contenedor de Postgres.
- `WHATSAPP_VERIFY_TOKEN`: string elegido por nosotros (no el WABA ID). Úsalo en la verificación del webhook.
- `LOG_CHANNEL=stderr`: envía todos los `Log::` de Laravel al `docker compose logs -f app`.

## Webhook de WhatsApp
- Meta no valida `localhost` directamente. Para pruebas locales, expone Laravel con ngrok desde el host:
  ```bash
  ngrok http 8000
  ```
- En Meta configura:
  - Callback URL: `https://<tu-subdominio-ngrok>/api/whatsapp/webhook`
  - Verify token: el valor de `WHATSAPP_VERIFY_TOKEN` en `.env`

## Comandos de ayuda (Makefile)
Si prefieres usar `make`:
- `make up` → `docker compose up -d --build`
- `make down` → `docker compose down`
- `make install` → `docker compose run --rm composer install`
- `make key` → `docker compose exec app php artisan key:generate`
- `make migrate` → `docker compose exec app php artisan migrate`
- `make test` → `docker compose exec app php artisan test`
- `make timeouts` → `docker compose exec app php artisan conversations:process-timeouts`
- `make logs` → `docker compose logs -f app`

## Testing
- Base actual: `phpunit.xml`, `tests/` y ejecución vía Laravel test runner.
- Comando recomendado:
  ```bash
  docker-compose exec app php artisan test
  ```
- Atajo equivalente:
  ```bash
  make test
  ```
- La política y criterios de cobertura están documentados en `docs/11-testing-y-criterios.md`.

## Diagramas como código
- La documentación visual vive en `docs/diagrams/`.
- Mermaid se usa para flujos conversacionales.
- PlantUML se usa para diagramas de clases.
- DBML se usa para esquema de base de datos.
- La convención y los criterios de actualización están en `docs/diagrams/README.md`.

## Archivos clave
- `docker-compose.yml`: orquesta `app`, `db` y `composer`.
- `docker/app/Dockerfile`: imagen PHP 8.3 con extensiones requeridas y usuario no-root.
- `.env.docker.example`: variables de entorno pensadas para Docker.
- `Makefile`: atajos para comandos frecuentes dentro de Docker.
- `docs/diagrams/`: documentación visual versionable del proyecto.
