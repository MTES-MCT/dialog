.PHONY: assets

# Allow non-Docker overrides for CI.
_DOCKER_EXEC_PHP = docker-compose exec php
_SYMFONY = ${_DOCKER_EXEC_PHP} symfony
BIN_PHP = ${_DOCKER_EXEC_PHP} php
BIN_CONSOLE = ${_SYMFONY} console
BIN_COMPOSER = ${_SYMFONY} composer
BIN_NPM = ${_DOCKER_EXEC_PHP} npm
BIN_NPX = ${_DOCKER_EXEC_PHP} npx
_DOCKER_COMPOSE_ADDOK = docker-compose -f ./docker-compose-addok.yml

# No TTY commands.
_DOCKER_EXEC_PHP_NO_TTY = docker-compose exec -T php
_SYMFONY_NO_TTY = ${_DOCKER_EXEC_PHP_NO_TTY} symfony
BIN_PHP_NO_TTY = ${_DOCKER_EXEC_PHP_NO_TTY} php
BIN_CONSOLE_NO_TTY = ${_SYMFONY_NO_TTY} console

##
## ----------------
## General
## ----------------
##

all: help

help: ## Display this message
	@grep -E '(^[a-zA-Z0-9_\-\.]+:.*?##.*$$)|(^##)' Makefile | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[32m%-30s\033[0m %s\n", $$1, $$2}' | sed -e 's/\[32m## /[33m/'

install: build start install_deps dbinstall assets blog_install ## Bootstrap project

install_deps: ## Install dependencies
	make composer CMD="install -n --prefer-dist"
	$(BIN_NPM) ci
	$(BIN_NPX) playwright install firefox chromium

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
	make data_install
	make console CMD="doctrine:database:create --env=test --if-not-exists"
	make dbmigrate ARGS="--env=test"
	make data_install ARGS="--env=test"
	make dbfixtures

dbmigration: ## Generate new db migration
	${BIN_CONSOLE} doctrine:migrations:diff

dbmigrate: ## Run db migration
	${BIN_CONSOLE} doctrine:migrations:migrate -n --all-or-nothing ${ARGS}

dbshell: ## Connect to the database
	docker-compose exec database psql postgresql://dialog:dialog@database:5432/dialog

dbfixtures: ## Load tests fixtures
	make console CMD="doctrine:fixtures:load --env=test -n --purge-with-truncate"

redisshell: ## Connect to the Redis container
	docker-compose exec redis redis-cli

addok_start: ## Start Addok containers
	${_DOCKER_COMPOSE_ADDOK} up -d

addok_stop: ## Stop Addok containers
	${_DOCKER_COMPOSE_ADDOK} stop

addok_ps: ## Display Addok containers
	${_DOCKER_COMPOSE_ADDOK} ps

addok_bundle: ## Create Addok custom bundle file
	make addok_stop
	${_DOCKER_COMPOSE_ADDOK} rm -f
	make addok_start
	sleep 5s

	${_DOCKER_COMPOSE_ADDOK} exec -e NO_DOWNLOAD=${NO_DOWNLOAD} addok_builder_db /data/run.sh

	${_DOCKER_COMPOSE_ADDOK} exec addok addok batch /data/junctions.json
	${_DOCKER_COMPOSE_ADDOK} exec addok addok ngrams

	${_DOCKER_COMPOSE_ADDOK} exec addok_redis redis-cli save
	sudo chmod +r docker/addok/addok-data/dump.rdb # Allow zip to read it

	cd docker/addok && zip -j addok-dialog-bundle.zip addok-data/addok.conf addok-data/addok.db addok-data/dump.rdb

data_install: data_init ## Load data into database
	make console CMD="app:data:fr_city ${ARGS}"

data_init: data/fr_city.sql ## Initialize data sources

data_update: ## Update data sources
	rm -f data/fr_city.sql data/communes.json
	make data_init

data/fr_city.sql: data/communes.json
	${BIN_PHP} tools/mkfrcitysql.php ./data/communes.json ./data/fr_city.sql

data/communes.json:
	curl -L https://unpkg.com/@etalab/decoupage-administratif/data/communes.json > data/communes.json

data_bac_idf_import: # Import BAC-IDF decrees as regulation orders
	make console CMD="app:bac_idf:import ${ARGS}"

data/decrees.json: ## Create BAC-IDF decrees file
	./tools/bacidfinstall

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
	${BIN_PHP} ./vendor/bin/phpstan analyse -l 5 src

phpstan_no_tty: ## PHP Stan without TTY
	${BIN_PHP_NO_TTY} ./vendor/bin/phpstan analyse -l 5 src

php_lint: ## PHP linter
	${BIN_PHP} ./vendor/bin/php-cs-fixer fix -n ${ARGS}

php_lint_no_tty: ## PHP linter without TTY
	${BIN_PHP_NO_TTY} ./vendor/bin/php-cs-fixer fix -n ${ARGS}

twig_lint: ## Twig linter
	${BIN_CONSOLE} lint:twig -n templates/

twig_lint_no_tty: ## Twig linter without TTY
	${BIN_CONSOLE_NO_TTY} lint:twig -n templates/

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

husky: ## Husky pre-commit hook
	make php_lint_no_tty ARGS="--dry-run"
	make phpstan_no_tty
	make twig_lint_no_tty

format: php_lint ## Format code

##
## ----------------
## Tests
## ----------------
##

test: ## Run the test suite
	${BIN_PHP} ${OPTIONS} ./bin/phpunit ${ARGS}
	make test_e2e ARGS="${PLAYWRIGHT_ARGS}"

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
## Blog
## ----------------
##

blog_install: blog_install_deps blog_build ## Bootstrap blog

blog_install_deps: ## Install blog dependencies
	${BIN_NPM} run blog_install

blog_build: ## Build blog
	${BIN_NPM} run blog_build

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
	make blog_install
	make check
	make test_cov

##
## ----------------
## Prod
## ----------------
##

scalingo-node-postbuild:
	make assets
	make blog_install

scalingo-postdeploy:
	@echo 'Executing migrations...'
	${BIN_CONSOLE} doctrine:migrations:migrate --no-interaction
