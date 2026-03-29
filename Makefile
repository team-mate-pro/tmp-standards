.PHONY: help tests check check_fast fix start stop fast t c cf f

## --- Mandatory variables ---

docker-compose=docker compose
main-container-name=app
vendor-dir=vendor/team-mate-pro/make/

help: ### Display available targets and their descriptions
	@echo "Usage: make [target]"
	@echo "Targets:"
	@awk 'BEGIN {FS = ":.*?### "}; /^[a-zA-Z_-]+:.*?### / {printf "\033[36m%-30s\033[0m %s\n", $$1, $$2}' $(MAKEFILE_LIST)
	@echo ""

## --- Shared includes (optional - won't fail if package not installed) ---

-include $(vendor-dir)git/MAKE_GIT_v1
-include $(vendor-dir)docker/MAKE_DOCKER_v1
-include $(vendor-dir)claude/MAKE_CLAUDE_v1
-include $(vendor-dir)phpunit/MAKE_PHPUNIT_v1
-include $(vendor-dir)phpstan/MAKE_PHPSTAN_v1
-include $(vendor-dir)phpcs/MAKE_PHPCS_v1

## --- Fallback targets (used when shared make package is not installed) ---

phpcs: ### [cs] Run PHPCS with configured standard and exclusions
	$(docker-compose) exec $(main-container-name) composer phpcs

phpstan: ### [ps] Run PHPStan with configured settings
	$(docker-compose) exec $(main-container-name) composer phpstan

tests_unit: ### [tu] Run unit tests
	$(docker-compose) exec $(main-container-name) composer tests:unit

## --- Mandatory aliases ---

start: ### Full start and rebuild of the container
	$(docker-compose) build
	$(docker-compose) up -d

fast: ### Fast start already built containers
	$(docker-compose) up -d

stop: ### Stop all existing containers
	$(docker-compose) down

check: ### [c] Should run all mandatory checks that run in CI and CD process
	make phpcs
	make phpstan
	make tests_unit

check_fast: ### [cf] Should run all mandatory checks that run in CI and CD process skipping heavy ones
	make phpcs
	make phpstan
	make tests_unit

fix: ### [f] Should run auto fix checks
	$(docker-compose) exec $(main-container-name) composer phpcs:fix

tests: ### [t] Run all tests
	$(docker-compose) exec $(main-container-name) composer tests

## --- Aliases ---

c: check
cf: check_fast
f: fix
t: tests
