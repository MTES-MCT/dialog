# Allow referring to environment variables defined in .env.
# See: https://lithic.tech/blog/2020-05/makefile-dot-env
ifneq (,$(wildcard ./.env))
	include .env
	export
endif

.PHONY: assets

# Allow non-Docker overrides for CI.
_DOCKER_EXEC_PHP = docker-compose exec php
_SYMFONY = ${_DOCKER_EXEC_PHP} symfony
BIN_PHP = ${_DOCKER_EXEC_PHP} php
BIN_CONSOLE = ${_SYMFONY} console
BIN_COMPOSER = ${_SYMFONY} composer
BIN_NPM = ${_DOCKER_EXEC_PHP} npm
BIN_NPX = ${_DOCKER_EXEC_PHP} npx

##
## ----------------
## General
## ----------------
##

all: help

help: ## Display this message
	@grep -E '(^[a-zA-Z0-9_\-\.]+:.*?##.*$$)|(^##)' Makefile | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[32m%-30s\033[0m %s\n", $$1, $$2}' | sed -e 's/\[32m## /[33m/'

install: build start install_deps dbinstall assets ## Bootstrap project

install_deps: ## Install dependencies
	make composer CMD="install -n --prefer-dist"
	$(BIN_NPM) ci
	$(BIN_NPX) playwright install --with-deps firefox chromium

update_deps:
	make composer CMD="update"
	$(BIN_NPM) update

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

dbinstall: ## Setup databases
	make dbmigrate
	make console CMD="doctrine:database:create --env=test --if-not-exists"
	make dbmigrate ARGS="--env=test"
	make dbfixtures

dbmigration: ## Generate new db migration
	${BIN_CONSOLE} doctrine:migrations:diff

dbmigrate: ## Run db migration
	${BIN_CONSOLE} doctrine:migrations:migrate -n --all-or-nothing ${ARGS}

dbshell: ## Connect to the database
	docker-compose exec database psql ${DATABASE_URL}

dbfixtures: ## Load tests fixtures
	make console CMD="doctrine:fixtures:load --env=test -n --purge-with-truncate"

##
## ----------------
## Executable
## ----------------
##

composer: ## Run composer commands
	${BIN_COMPOSER} ${CMD}

console: ## Run console command
	${BIN_CONSOLE} ${CMD}

cc: ## Run clear cache command
	${BIN_CONSOLE} cache:clear

watch: ## Watch assets
	$(BIN_NPM) run watch

assets: ## Build assets
	$(BIN_NPM) run build

shell: ## Connect to the container
	docker-compose exec php bash

##
## ----------------
## Quality
## ----------------
##

# Individual tools

phpstan: ## PHP Stan
	${BIN_PHP} ./vendor/bin/phpstan analyse -l 5 --xdebug src

php_lint: ## PHP linter
	${BIN_PHP} ./vendor/bin/php-cs-fixer fix -n ${ARGS}

twig_lint: ## Twig linter
	${BIN_CONSOLE} lint:twig -n

security_check: ## Security checks
	${_SYMFONY} security:check

psr_lint: ## Check PSR autoloading
	${BIN_COMPOSER} dump-autoload --strict-psr

# All-in-one commands

check: ## Run checks
	make php_lint ARGS="--dry-run"
	make psr_lint
	make twig_lint
	make phpstan
	${BIN_CONSOLE} doctrine:schema:validate

format: php_lint ## Format code

##
## ----------------
## Tests
## ----------------
##

test: ## Run the test suite
	${BIN_PHP} ${OPTIONS} ./bin/phpunit ${ARGS}
	make test_e2e ARGS=""

test_cov: ## Run the test suite (with code coverage)
	make test OPTIONS="-d xdebug.mode=coverage" ARGS="--coverage-html coverage --coverage-clover coverage.xml"

test_unit: ## Run unit tests only
	${BIN_PHP} ./bin/phpunit --testsuite=Unit ${ARGS}

test_integration: ## Run integration tests only
	${BIN_PHP} ./bin/phpunit --testsuite=Integration ${ARGS}

test_e2e: ## Run end-to-end tests only
	make dbfixtures
	$(BIN_NPX) playwright test --project desktop-firefox ${ARGS}
	make dbfixtures
	$(BIN_NPX) playwright test --project mobile-chromium ${ARGS}

report_e2e: ## Open the Playwright HTML report
	xdg-open playwright-report/index.html

##
## ----------------
## CI
## ----------------
##

ci: ## Run CI steps
	make install_deps
	make assets
	make dbinstall
	make dbfixtures
	make check
	make test_cov
