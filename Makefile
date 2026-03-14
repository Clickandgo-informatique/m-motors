# =========================
# CONFIG
# =========================

DC = docker compose
PHP = $(DC) exec php
CONSOLE = $(PHP) php bin/console
COMPOSER = $(PHP) composer

# =========================
# DOCKER
# =========================

up:
	$(DC) up -d

down:
	$(DC) down

restart:
	$(DC) down
	$(DC) up -d

build:
	$(DC) build

logs:
	$(DC) logs -f

ps:
	$(DC) ps

# =========================
# CONTAINER
# =========================

bash:
	$(PHP) bash

# =========================
# COMPOSER
# =========================

composer-install:
	$(COMPOSER) install

composer-update:
	$(COMPOSER) update

composer-require:
	$(COMPOSER) require $(pkg)

# exemple :
# make composer-require pkg=symfony/ux-live-component

# =========================
# SYMFONY
# =========================

cache-clear:
	$(CONSOLE) cache:clear

cache-warmup:
	$(CONSOLE) cache:warmup

debug-router:
	$(CONSOLE) debug:router

debug-container:
	$(CONSOLE) debug:container

# =========================
# DATABASE
# =========================

db-create:
	$(CONSOLE) doctrine:database:create

db-drop:
	$(CONSOLE) doctrine:database:drop --force --if-exists

db-reset:
	$(CONSOLE) doctrine:database:drop --force --if-exists
	$(CONSOLE) doctrine:database:create
	$(CONSOLE) doctrine:migrations:migrate --no-interaction

fixtures:
	$(CONSOLE) doctrine:fixtures:load --no-interaction

db-fixtures:
	$(CONSOLE) doctrine:database:drop --force --if-exists
	$(CONSOLE) doctrine:database:create
	$(CONSOLE) doctrine:migrations:migrate --no-interaction
	$(CONSOLE) doctrine:fixtures:load --no-interaction

# =========================
# MIGRATIONS
# =========================

migration:
	$(CONSOLE) make:migration

migrate:
	$(CONSOLE) doctrine:migrations:migrate

migration-clean:
	rm -f migrations/*.php

migration-reset:
	rm -f migrations/*.php
	$(CONSOLE) make:migration

# =========================
# FULL RESET DEV
# =========================

db-fresh:
	rm -f migrations/*.php
	$(CONSOLE) doctrine:database:drop --force --if-exists
	$(CONSOLE) doctrine:database:create
	$(CONSOLE) make:migration
	$(CONSOLE) doctrine:migrations:migrate --no-interaction
	$(CONSOLE) doctrine:fixtures:load --no-interaction

# =========================
# QUALITY / DEBUG
# =========================

validate-schema:
	$(CONSOLE) doctrine:schema:validate

debug-entities:
	$(CONSOLE) doctrine:mapping:info

# =========================
# TESTS
# =========================

tests:
	$(PHP) php bin/phpunit

# =========================
# PERMISSIONS (Linux)
# =========================

perm:
	sudo chown -R $$USER:$$USER .

# =========================
# DEPLOY (PRODUCTION)
# =========================

deploy:
	git pull
	$(DC) build
	$(DC) up -d
	$(PHP) composer install --no-dev --optimize-autoloader
	$(CONSOLE) doctrine:migrations:migrate --no-interaction
	$(CONSOLE) cache:clear
	$(CONSOLE) cache:warmup

deploy-fast:
	git pull
	$(DC) up -d
	$(CONSOLE) doctrine:migrations:migrate --no-interaction
	$(CONSOLE) cache:clear