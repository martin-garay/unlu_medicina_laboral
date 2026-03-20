# Cargar variables desde .env (si existe)
-include .env
export


UID := $(shell id -u)
GID := $(shell id -g)
DC := DOCKER_CONFIG=$(CURDIR)/.docker docker-compose


.PHONY: help db ps up down restart setup install key migrate logs sh artisan doctor test test-unit test-feature timeouts timeouts-now schedule-run diagrams diagrams-check diagrams-clean

help:
	@printf "%s\n" \
	"make up            - levanta contenedores en segundo plano con build" \
	"make down          - baja los contenedores" \
	"make restart       - reinicia el contenedor app" \
	"make ps            - lista el estado de los contenedores" \
	"make setup         - install + key + migrate" \
	"make install       - instala dependencias PHP" \
	"make key           - genera APP_KEY" \
	"make migrate       - corre migraciones" \
	"make logs          - sigue logs del contenedor app" \
	"make sh            - shell dentro del contenedor app" \
	"make artisan CMD='about' - ejecuta un comando artisan arbitrario" \
	"make doctor        - chequeo operativo rápido del entorno" \
	"make test          - corre toda la suite" \
	"make test-unit     - corre tests unitarios" \
	"make test-feature  - corre tests feature" \
	"make schedule-run  - ejecuta una pasada del scheduler" \
	"make timeouts      - procesa timeouts con hora actual" \
	"make timeouts-now NOW='2026-03-20 10:00:00' - procesa timeouts con hora fija" \
	"make diagrams      - regenera diagramas renderizados"

ps:
	UID=$(UID) GID=$(GID) $(DC) ps

up:
	UID=$(UID) GID=$(GID) $(DC) up -d --build

down:
	UID=$(UID) GID=$(GID) $(DC) down

restart:
	UID=$(UID) GID=$(GID) $(DC) restart app

setup: install key migrate

install:
	UID=$(UID) GID=$(GID) $(DC) run --rm -e COMPOSER_ALLOW_SUPERUSER=1 composer install

key:
	UID=$(UID) GID=$(GID) $(DC) exec app php artisan key:generate

migrate:
	UID=$(UID) GID=$(GID) $(DC) exec app php artisan migrate

logs:
	UID=$(UID) GID=$(GID) $(DC) logs -f app

sh:
	UID=$(UID) GID=$(GID) $(DC) exec app sh

artisan:
	UID=$(UID) GID=$(GID) $(DC) exec app php artisan $(CMD)

doctor:
	UID=$(UID) GID=$(GID) $(DC) exec app php artisan medicina:doctor

db:
	UID=$(UID) GID=$(GID) $(DC) exec db \
	psql -h localhost -U $(DB_USERNAME) -d $(DB_DATABASE)

test:
	UID=$(UID) GID=$(GID) $(DC) exec app php artisan test

test-unit:
	UID=$(UID) GID=$(GID) $(DC) exec app php artisan test --testsuite=Unit

test-feature:
	UID=$(UID) GID=$(GID) $(DC) exec app php artisan test --testsuite=Feature

timeouts:
	UID=$(UID) GID=$(GID) $(DC) exec app php artisan conversations:process-timeouts

timeouts-now:
	UID=$(UID) GID=$(GID) $(DC) exec app php artisan conversations:process-timeouts --now="$(NOW)"

schedule-run:
	UID=$(UID) GID=$(GID) $(DC) exec app php artisan schedule:run

diagrams:
	./scripts/render_diagrams.sh

diagrams-check:
	./scripts/render_diagrams.sh
	git diff --exit-code -- docs/diagrams/rendered

diagrams-clean:
	rm -rf docs/diagrams/rendered/flows docs/diagrams/rendered/classes
