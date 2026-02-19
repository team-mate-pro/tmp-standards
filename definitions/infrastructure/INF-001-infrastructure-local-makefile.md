# INF-001: Local Development Makefile Standard

## Check Method

| Method | Command |
|--------|---------|
| **SCRIPT** | `./vendor/team-mate-pro/tmp-standards/definitions/infrastructure/INF-001-infrastructure-local-makefile.sh` |

## Definition

Every TMP project must include a `Makefile` in the repository root that provides a consistent set of commands for local development, testing, and CI/CD validation.

## Required Commands

Every project **must** implement these mandatory targets:

| Command | Description |
|---------|-------------|
| `make start` | Full start and rebuild of containers (clean start) |
| `make stop` | Stop all running containers |
| `make fast` | Fast start of already built containers (no rebuild) |
| `make check` | Run all CI/CD validation steps locally |

## Recommended Commands

| Command | Description |
|---------|-------------|
| `make help` | Display available targets with descriptions |
| `make check_fast` | Run quick checks (skip slow tests) |
| `make fix` | Auto-fix code style issues |
| `make tests` | Run all project tests |

## Correct Usage

### Backend (PHP/Symfony)

```makefile
.PHONY: help start fast stop check check_fast fix tests

## --- Mandatory variables ---

docker-compose=docker compose
main-container-name=app
vendor-dir=vendor/team-mate-pro/make/

help: ### Display available targets and their descriptions
	@echo "Usage: make [target]"
	@awk 'BEGIN {FS = ":.*?### "}; /^[a-zA-Z_-]+:.*?### / {printf "\033[36m%-30s\033[0m %s\n", $$1, $$2}' $(MAKEFILE_LIST)

## --- Shared includes (optional - won't fail if package not installed) ---

-include $(vendor-dir)claude/MAKE_CLAUDE_v1
-include $(vendor-dir)git/MAKE_GIT_v1
-include $(vendor-dir)docker/MAKE_DOCKER_v1
-include $(vendor-dir)sf-7/MAKE_SYMFONY_v1
-include $(vendor-dir)phpcs/MAKE_PHPCS_v1
-include $(vendor-dir)phpunit/MAKE_PHPUNIT_v1
-include $(vendor-dir)phpstan/MAKE_PHPSTAN_v1

## --- Mandatory targets ---

start: ### Full start and rebuild of the container
	./tools/dev/stop.sh
	./tools/dev/start.sh

fast: ### Fast start already built containers
	./tools/dev/fast.sh

stop: ### Stop all existing containers
	./tools/dev/stop.sh

check: ### Run all mandatory checks (CI/CD validation)
	make phpcs
	make phpstan
	make tests

check_fast: ### Run quick checks (skip heavy tests)
	make phpcs_fix
	make phpcs
	make phpstan

fix: ### Auto-fix code style issues
	make phpcs_fix

tests: ### Run all tests
	$(docker-compose) exec -it $(main-container-name) composer tests

## --- Aliases ---

c: check
cf: check_fast
f: fix
t: tests
```

### Frontend (Node.js/Nuxt)

```makefile
.PHONY: help start fast stop check check_fast fix tests lint

## --- Mandatory variables ---

docker-compose=docker compose
main-container-name=app
vendor-dir=node_modules/@team-mate-pro/make/

help: ### Display available targets and their descriptions
	@echo "Usage: make [target]"
	@awk 'BEGIN {FS = ":.*?### "}; /^[a-zA-Z_-]+:.*?### / {printf "\033[36m%-30s\033[0m %s\n", $$1, $$2}' $(MAKEFILE_LIST)

## --- Shared includes (optional - won't fail if package not installed) ---

-include $(vendor-dir)claude/MAKE_CLAUDE_v1
-include $(vendor-dir)git/MAKE_GIT_v1
-include $(vendor-dir)docker/MAKE_DOCKER_v1

## --- Mandatory targets ---

start: ### Full start and rebuild of the container
	./tools/dev/stop.sh
	./tools/dev/start.sh

fast: ### Fast start already built containers
	./tools/dev/fast.sh

stop: ### Stop all existing containers
	./tools/dev/stop.sh

check: ### Run all mandatory checks (CI/CD validation)
	make lint
	make tests

check_fast: ### Run quick checks (lint only)
	make lint

fix: ### Auto-fix code style issues
	$(docker-compose) exec $(main-container-name) npm run lint:fix

tests: ### Run all tests
	$(docker-compose) exec $(main-container-name) npm run test

lint: ### Run linter
	$(docker-compose) exec $(main-container-name) npm run lint

## --- Aliases ---

c: check
cf: check_fast
f: fix
t: tests
```

## Shared Make Package

Use the `team-mate-pro/make` package to include reusable Makefile fragments:

### PHP Projects (Composer)

```bash
composer require team-mate-pro/make --dev
```

```makefile
vendor-dir=vendor/team-mate-pro/make/
-include $(vendor-dir)docker/MAKE_DOCKER_v1
-include $(vendor-dir)phpcs/MAKE_PHPCS_v1
# ... etc
```

### Node.js Projects (NPM)

```bash
npm install @team-mate-pro/make --save-dev
```

```makefile
vendor-dir=node_modules/@team-mate-pro/make/
-include $(vendor-dir)docker/MAKE_DOCKER_v1
# ... etc
```

## Optional Includes

Always use `-include` (with leading dash) instead of `include` for shared make files:

```makefile
# CORRECT: Won't fail if file doesn't exist
-include $(vendor-dir)docker/MAKE_DOCKER_v1

# WRONG: Will fail on first run before dependencies are installed
include $(vendor-dir)docker/MAKE_DOCKER_v1
```

**Why this matters:**

On first clone, before running `composer install` or `npm install`, the vendor directory doesn't exist. Using `include` would cause Make to fail with:

```
Makefile:21: vendor/team-mate-pro/make/docker/MAKE_DOCKER_v1: No such file or directory
make: *** No rule to make target 'vendor/team-mate-pro/make/docker/MAKE_DOCKER_v1'. Stop.
```

Using `-include` (or `sinclude`) silently skips missing files, allowing `make start` to work even before dependencies are installed.

## Violation

```makefile
# WRONG: Missing mandatory targets
.PHONY: run build

run:
	docker compose up

build:
	docker compose build

# Missing: start, stop, fast, check
```

```makefile
# WRONG: Inconsistent naming
.PHONY: up down rebuild

up:           # Should be: start
	./start.sh

down:         # Should be: stop
	./stop.sh

rebuild:      # Should be: fast (or start)
	./fast.sh

# Missing: check
```

## Mandatory Variables

Every Makefile must define these variables at the top:

| Variable | Description | Example |
|----------|-------------|---------|
| `docker-compose` | Docker Compose command | `docker compose` |
| `main-container-name` | Primary application container | `app` |
| `vendor-dir` | Path to shared make includes | `vendor/team-mate-pro/make/` |

## Help Target Format

Use the `### ` comment format for self-documenting targets:

```makefile
start: ### Full start and rebuild of the container
	./tools/dev/start.sh
```

This enables `make help` to display:
```
start                          Full start and rebuild of the container
```

## Aliases

Provide short aliases for frequently used commands:

| Alias | Target |
|-------|--------|
| `c` | `check` |
| `cf` | `check_fast` |
| `f` | `fix` |
| `t` | `tests` |

## Validation Script

A bash script is provided to validate your project's Makefile against this standard.

### Usage

```bash
# From project root (after installing tmp-standards)
./vendor/team-mate-pro/tmp-standards/definitions/infrastructure/INF-001-infrastructure-local-makefile.sh

# Or specify a path
./vendor/team-mate-pro/tmp-standards/definitions/infrastructure/INF-001-infrastructure-local-makefile.sh /path/to/project
```

### Example Output

```
╔════════════════════════════════════════════════════════════╗
║  INF-001: Local Development Makefile Standard Validator    ║
╚════════════════════════════════════════════════════════════╝

Checking: ./Makefile

Required Targets:
  ✓ make start
  ✓ make stop
  ✓ make fast
  ✓ make check

Recommended Targets:
  ✓ make help
  ✓ make check_fast
  ✓ make fix
  ✓ make tests

Required Variables:
  ✓ docker-compose
  ✓ main-container-name
  ✓ vendor-dir

Include Syntax:
  ✓ All includes use optional syntax (-include)

Self-Documentation:
  ✓ Uses ### comment format for help

Common Aliases:
  ✓ Found 4/4 common aliases (c, cf, f, t)

════════════════════════════════════════════════════════════
✓ PASSED - Makefile conforms to INF-001 standard
```

### Exit Codes

| Code | Meaning |
|------|---------|
| `0` | Makefile conforms to INF-001 |
| `1` | Validation failed (missing required targets) |

## Rationale

1. **Consistency**: Developers can switch between projects without learning new commands.

2. **CI/CD Parity**: `make check` ensures local validation matches CI/CD pipeline.

3. **Onboarding**: New developers can start any project with `make start` and validate with `make check`.

4. **Documentation**: Self-documenting targets via `make help` reduce need for external docs.

5. **Modularity**: Shared make includes from `team-mate-pro/make` reduce duplication across projects.
