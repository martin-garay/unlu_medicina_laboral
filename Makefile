# Helpers to keep every command inside Docker
UID := $(shell id -u)
GID := $(shell id -g)

.PHONY: up down install key migrate logs sh

up:
docker compose up -d --build

down:
docker compose down

install:
docker compose run --rm -e COMPOSER_ALLOW_SUPERUSER=1 composer install

key:
docker compose exec app php artisan key:generate

migrate:
docker compose exec app php artisan migrate

logs:
docker compose logs -f app

sh:
docker compose exec app sh
