# Cargar variables desde .env (si existe)
-include .env
export


UID := $(shell id -u)
GID := $(shell id -g)
DC := DOCKER_CONFIG=$(CURDIR)/.docker docker-compose


.PHONY: db up down install key migrate logs sh test timeouts diagrams diagrams-check diagrams-clean

up:
	UID=$(UID) GID=$(GID) $(DC) up --build

down:
	UID=$(UID) GID=$(GID) $(DC) down

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

db:
	UID=$(UID) GID=$(GID) $(DC) exec db \
	psql -h localhost -U $(DB_USERNAME) -d $(DB_DATABASE)

test:
	UID=$(UID) GID=$(GID) $(DC) exec app php artisan test

timeouts:
	UID=$(UID) GID=$(GID) $(DC) exec app php artisan conversations:process-timeouts

diagrams:
	./scripts/render_diagrams.sh

diagrams-check:
	./scripts/render_diagrams.sh
	git diff --exit-code -- docs/diagrams/rendered

diagrams-clean:
	rm -rf docs/diagrams/rendered/flows docs/diagrams/rendered/classes
