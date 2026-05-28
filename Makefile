dc = docker compose
php = $(dc) exec php
console = $(dc) exec php bin/console
composer = $(dc) exec php composer

.PHONY: up down restart build logs ps bash \
	composer-install composer-update composer-require \
	cache-clear cache-warmup debug-router debug-container \
	db-create db-drop db-reset fixtures db-fixtures \
	migration migrate migration-clean migration-reset \
	db-fresh validate-schema debug-entities \
	tests perm deploy deploy-fast coverage-html coverage-text coverage-clover coverage-cobertura coverage-all

up:
	$(dc) up -d

down:
	$(dc) down

restart:
	$(dc) down
	$(dc) up -d

build:
	$(dc) build

logs:
	$(dc) logs -f

ps:
	$(dc) ps

bash:
	$(php) bash

composer-install:
	$(composer) install

composer-update:
	$(composer) update

composer-require:
	$(composer) require $(pkg)

cache-clear:
	$(console) cache:clear

cache-warmup:
	$(console) cache:warmup

debug-router:
	$(console) debug:router

debug-container:
	$(console) debug:container

db-create:
	$(console) doctrine:database:create

db-drop:
	$(console) doctrine:database:drop --force --if-exists

db-reset:
	$(console) doctrine:database:drop --force --if-exists
	$(console) doctrine:database:create
	$(console) doctrine:migrations:migrate --no-interaction

fixtures:
	$(console) doctrine:fixtures:load --no-interaction

db-fixtures:
	$(console) doctrine:database:drop --force --if-exists
	$(console) doctrine:database:create
	$(console) doctrine:migrations:migrate --no-interaction
	$(console) doctrine:fixtures:load --no-interaction

migration:
	$(console) make:migration

migrate:
	$(console) doctrine:migrations:migrate

migration-clean:
	rm -f migrations/*.php

migration-reset:
	rm -f migrations/*.php
	$(console) make:migration

db-fresh:
	rm -f migrations/*.php
	$(console) doctrine:database:drop --force --if-exists
	$(console) doctrine:database:create
	$(console) make:migration
	$(console) doctrine:migrations:migrate --no-interaction
	$(console) doctrine:fixtures:load --no-interaction

validate-schema:
	$(console) doctrine:schema:validate

debug-entities:
	$(console) doctrine:mapping:info

tests:
	clear
	$(php) vendor/bin/phpunit

perm:
	sudo chown -R $$USER:$$USER .

deploy:
	git pull
	$(dc) build
	$(dc) up -d
	$(php) composer install --no-dev --optimize-autoloader
	$(console) doctrine:migrations:migrate --no-interaction
	$(console) cache:clear
	$(console) cache:warmup

deploy-fast:
	git pull
	$(dc) up -d
	$(console) doctrine:migrations:migrate --no-interaction
	$(console) cache:clear

coverage-html:
	$(dc) exec php vendor/bin/phpunit --coverage-html var/coverage

coverage-text:
	$(dc) exec php vendor/bin/phpunit --coverage-text

coverage-clover:
	$(dc) exec php vendor/bin/phpunit --coverage-clover var/coverage/clover.xml

coverage-cobertura:
	$(dc) exec php vendor/bin/phpunit --coverage-cobertura var/coverage/cobertura.xml

coverage-all: coverage-html coverage-text coverage-clover coverage-cobertura