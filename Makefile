# =========================
# CONFIG (auto local / docker)
# =========================

DC = docker compose

# Détection du container PHP
DOCKER_PHP = $(shell docker ps --format "{{.Names}}" | grep -w php 2>/dev/null)

ifeq ($(DOCKER_PHP),php)
    # Mode DOCKER
    PHP = $(DC) exec php
    CONSOLE = $(PHP) php bin/console
    COMPOSER = $(PHP) composer
else
    # Mode LOCAL
    PHP = php
    CONSOLE = php bin/console
    COMPOSER = composer
endif


# =========================
# PHONY
# =========================

.PHONY: up down restart build logs ps bash \
        composer-install composer-update composer-require \
        cache-clear cache-warmup debug-router debug-container \
        db-create db-drop db-reset fixtures db-fixtures \
        migration migrate migration-clean migration-reset \
        db-fresh validate-schema debug-entities \
        tests perm deploy deploy-fast

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
	clear
	docker compose exec php vendor/bin/phpunit

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

# Coverage HTML (rapport lisible dans un navigateur)
coverage-html:
	$(DC) exec php vendor/bin/phpunit --coverage-html var/coverage

# Coverage texte (affiché dans le terminal)
coverage-text:
	$(DC) exec php vendor/bin/phpunit --coverage-text

# Coverage Clover (pour GitLab CI, SonarQube, PHPStorm)
coverage-clover:
	$(DC) exec php vendor/bin/phpunit --coverage-clover var/coverage/clover.xml

# Coverage Cobertura (pour Jenkins, Azure DevOps, GitHub Actions)
coverage-cobertura:
	$(DC) exec php vendor/bin/phpunit --coverage-cobertura var/coverage/cobertura.xml

# Tout générer d'un coup
coverage-all: coverage-html coverage-text coverage-clover coverage-cobertura
