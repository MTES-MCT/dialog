# Allow referring to environment variables defined in .env.
# See: https://lithic.tech/blog/2020-05/makefile-dot-env
ifneq (,$(wildcard ./.env))
	include .env
	export
endif

COMPOSE_EXEC_PHP=docker-compose exec php

##
## ----------------
## General
## ----------------
##

all: help

help: ## Display this message
	@grep -E '(^[a-zA-Z0-9_\-\.]+:.*?##.*$$)|(^##)' Makefile | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[32m%-30s\033[0m %s\n", $$1, $$2}' | sed -e 's/\[32m## /[33m/'

install: build start ## Bootstrap project
	make composer CMD="install"
	make database_run_migration

start: ## Start container
	docker-compose up -d
	docker-compose start

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
## Database
## ----------------
##

database_generate_migration: ## Generate new db migration
	${COMPOSE_EXEC_PHP} symfony console doctrine:migrations:diff

database_run_migration: ## Run db migration
	${COMPOSE_EXEC_PHP} symfony console doctrine:migrations:migrate -n --all-or-nothing

database_connect: ## Connect to the database
	docker-compose exec database psql ${DATABASE_URL}

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

phpstan: ## PHP Stan
	${COMPOSE_EXEC_PHP} ./vendor/bin/phpstan analyse src

php_lint: ## PHP linter
	${COMPOSE_EXEC_PHP} ./vendor/bin/php-cs-fixer fix

twig_lint: ## Twig linter
	${COMPOSE_EXEC_PHP} symfony console lint:twig

lint: php_lint twig_lint ## Run linters

security_check: ## Security checks
	${COMPOSE_EXEC_PHP} symfony security:check

##
## ----------------
## Tests
## ----------------
##

phpunit: ## Run PHPUnit
	${COMPOSE_EXEC_PHP} ./bin/phpunit

phpunit_unit: ## Run unit tests
	${COMPOSE_EXEC_PHP} ./bin/phpunit --testsuite=Unit ${ARGS}
