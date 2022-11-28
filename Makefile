# Allow referring to environment variables defined in .env.
# See: https://lithic.tech/blog/2020-05/makefile-dot-env
ifneq (,$(wildcard ./.env))
	include .env
	export
endif

# Allow non-Docker overrides for CI.
_DOCKER_EXEC_PHP = docker-compose exec php
_SYMFONY = ${_DOCKER_EXEC_PHP} symfony
PHP = ${_DOCKER_EXEC_PHP} php
CONSOLE = ${_SYMFONY} console
COMPOSER = ${_SYMFONY} composer

##
## ----------------
## General
## ----------------
##

all: help

help: ## Display this message
	@grep -E '(^[a-zA-Z0-9_\-\.]+:.*?##.*$$)|(^##)' Makefile | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[32m%-30s\033[0m %s\n", $$1, $$2}' | sed -e 's/\[32m## /[33m/'

install: build start install_deps migrate ## Bootstrap project

install_deps: ## Install dependencies
	make composer CMD="install -n --prefer-dist"

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

migration: ## Generate new db migration
	${CONSOLE} doctrine:migrations:diff

migrate: ## Run db migration
	${CONSOLE} doctrine:migrations:migrate -n --all-or-nothing

dbshell: ## Connect to the database
	docker-compose exec database psql ${DATABASE_URL}

##
## ----------------
## Executable
## ----------------
##

composer: ## Run composer commands
	${COMPOSER} ${CMD}

console: ## Run console command
	${CONSOLE} ${CMD}

cache_clear: ## Run console command
	${CONSOLE} cache:clear

##
## ----------------
## Quality
## ----------------
##

check: ## Run checks
	${PHP} ./vendor/bin/php-cs-fixer fix -n --dry-run
	${CONSOLE} lint:twig -n
	${PHP} ./vendor/bin/phpstan analyse -l 5 src
	${CONSOLE} doctrine:schema:validate

format: ## Format code
	${PHP} ./vendor/bin/php-cs-fixer fix

security: ## Run security checks
	${_SYMFONY} security:check

##
## ----------------
## Tests
## ----------------
##

test: ## Run the test suite
	${PHP} -d xdebug.mode=coverage ./vendor/bin/phpunit --coverage-clover coverage.xml

test_unit: ## Run unit tests only
	${PHP} ./bin/phpunit --testsuite=Unit ${ARGS}

test_integration: ## Run integration tests only
	${PHP} ./bin/phpunit --testsuite=Integration ${ARGS}

ci: ## Run CI steps
	make install_deps
	make check
	make test
