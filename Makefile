help:
	@echo "\033[33mUsage:\033[0m"
	@echo "  make [command]"
	@echo ""
	@echo "\033[33mAvailable commands:\033[0m"
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort \
		| awk 'BEGIN {FS = ":.*?## "}; {printf "  \033[32m%s\033[0m___%s\n", $$1, $$2}' | column -ts___

.PHONY: build
build: # Builds or rebuilds the Docker images
	@echo "\033[1m\033[36m> Building Docker images\033[0m\033[21m"
	@docker-compose "build"

run: # Runs the application with Docker
	@echo "\033[1m\033[36m> Creating Docker containers\033[0m\033[21m"
	@docker-compose up -d
	@echo "\033[1m\033[36m> Accessing application container\033[0m\033[21m"
	@docker-compose exec php bash

stop: # Shuts down all Docker containers
	@echo "\033[1m\033[36m> Shutting down Docker containers\033[0m\033[21m"
	@docker-compose down