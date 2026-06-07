DC      = docker compose
PHP     = $(DC) exec php
PHP_TTY = $(DC) exec -T php

help:
	@echo "\033[33mUsage:\033[0m"
	@echo "  make [command]"
	@echo ""
	@echo "\033[33mAvailable commands:\033[0m"
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort \
		| awk 'BEGIN {FS = ":.*?## "}; {printf "  \033[32m%s\033[0m___%s\n", $$1, $$2}' | column -ts___

# -----------------------------------------------------------------------------
# Stack lifecycle
# -----------------------------------------------------------------------------

build: ## Builds (or rebuilds) the Docker images
	@echo "\033[1m\033[36m> Building Docker images\033[0m\033[21m"
	@$(DC) build

up: ## Starts the Docker stack in the background
	@echo "\033[1m\033[36m> Starting the Docker stack\033[0m\033[21m"
	@$(DC) up -d

down: ## Stops the Docker stack
	@echo "\033[1m\033[36m> Stopping the Docker stack\033[0m\033[21m"
	@$(DC) down

restart: ## Restarts the Docker stack
	@$(DC) restart

logs: ## Tails logs from all containers
	@$(DC) logs -f

ps: ## Lists running containers
	@$(DC) ps

shell: ## Opens a shell into the PHP container
	@$(PHP) sh

mysql: ## Opens a MySQL CLI inside the mysql container
	@$(DC) exec mysql mysql -u root redcall_prod

# -----------------------------------------------------------------------------
# Application dependencies
# -----------------------------------------------------------------------------

install: install-composer install-yarn ## Installs all project dependencies

install-composer: ## Installs PHP dependencies inside the PHP container
	@echo "\033[1m\033[36m> Installing Composer dependencies\033[0m\033[21m"
	@$(PHP) composer install

install-yarn: ## Installs Node dependencies on the host
	@echo "\033[1m\033[36m> Installing Yarn dependencies\033[0m\033[21m"
	@cd symfony && yarn install --cache-min 99999 --progress=false
	@cd symfony && yarn encore dev

# -----------------------------------------------------------------------------
# Database
# -----------------------------------------------------------------------------

db-import: ## Imports the local host MySQL dev DB into the containerised MySQL
	@echo "\033[1m\033[36m> Dumping host MySQL and importing into Docker\033[0m\033[21m"
	@mysqldump -h 127.0.0.1 -u root --single-transaction --no-tablespaces --routines --triggers redcall_prod \
		| $(DC) exec -T mysql mysql -u root redcall_prod

db-migrate: ## Runs pending Doctrine migrations
	@$(PHP) php bin/console doctrine:migrations:migrate -n

# -----------------------------------------------------------------------------
# Tests
# -----------------------------------------------------------------------------

test: ## Reinitializes the test database and runs all tests
	@echo "\033[1m\033[36m> Dropping test database\033[0m\033[21m"
	@$(PHP) sh -c 'APP_ENV=test php bin/console doctrine:database:drop --force --if-exists'
	@echo "\033[1m\033[36m> Creating test database\033[0m\033[21m"
	@$(PHP) sh -c 'APP_ENV=test php bin/console doctrine:database:create'
	@echo "\033[1m\033[36m> Creating schema from entities\033[0m\033[21m"
	@$(PHP) sh -c 'APP_ENV=test php bin/console doctrine:schema:create'
	@echo "\033[1m\033[36m> Running tests\033[0m\033[21m"
	@$(PHP) php vendor/bin/phpunit

.PHONY: help build up down restart logs ps shell mysql install install-composer install-yarn db-import db-migrate test
