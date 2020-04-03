help:
	@echo "\033[33mUsage:\033[0m"
	@echo "  make [command]"
	@echo ""
	@echo "\033[33mAvailable commands:\033[0m"
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort \
		| awk 'BEGIN {FS = ":.*?## "}; {printf "  \033[32m%s\033[0m___%s\n", $$1, $$2}' | column -ts___

install: install-composer install-yarn ## Installs all project dependencies

install-composer: ## Installs all PHP dependencies
	@echo "\033[1m\033[36m> Installing all Composer dependencies\033[0m\033[21m"
	@composer install

install-yarn: ## Installs all Node dependencies
	@echo "\033[1m\033[36m> Installing all Yarn dependencies\033[0m\033[21m"
	@yarn install --cache-min 99999 --progress=false
	@yarn encore dev

reset: reset-db ## Resets the development environment

reset-db: ## Resets the development database
	@echo "\033[1m\033[36m> Resetting development database\033[0m\033[21m"
	./dev/database/reset.sh

run: # Runs the application on a local web server
	@echo "\033[1m\033[36m> Starting the application\033[0m\033[21m"
	@php bin/console server:start

stop: # Stops the application
	@echo "\033[1m\033[36m> Stopping the application\033[0m\033[21m"
	@php bin/console server:stop
