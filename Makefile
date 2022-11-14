##
## ----------------
## General
## ----------------
##

COMPOSE_EXEC_PHP=docker-compose exec app

all: help

help: ## Display this message
	@grep -E '(^[a-zA-Z0-9_\-\.]+:.*?##.*$$)|(^##)' Makefile | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[32m%-30s\033[0m %s\n", $$1, $$2}' | sed -e 's/\[32m## /[33m/'

install: build start ## Bootstrap project
	make composer CMD="install"

start: ## Start container
	docker-compose up -d
	docker-compose start
	${COMPOSE_EXEC_PHP} symfony server:start -d --no-tls

stop: ## Stop containers
	docker-compose stop

ps: ## Display containers
	docker-compose ps

restart: stop start ## Restart containers

build: ## Build containers
	docker-compose build

rm: ## Remove containers
	make stop
	docker-compose rm

##
## ----------------
## Logs
## ----------------
##

logs: ## Run logs
	${COMPOSE_EXEC_PHP} symfony server:log

##
## ----------------
## Executable
## ----------------
##

composer: ## Run composer commands
	${COMPOSE_EXEC_PHP} symfony composer ${CMD}

console: ## Run console command
	${COMPOSE_EXEC_PHP} symfony console ${CMD}

cache_clear: ## Run console command
	${COMPOSE_EXEC_PHP} symfony console cache:clear

##
## ----------------
## Quality
## ----------------
##

lint: ## PHP linter
	${COMPOSE_EXEC_PHP} ./vendor/bin/php-cs-fixer fix

security_check: ## Security checks
	${COMPOSE_EXEC_PHP} symfony security:check
