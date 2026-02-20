# TEST-001: Unified PHPUnit Test Structure

## Check Method

| Method | Command |
|--------|---------|
| **SCRIPT** | `./vendor/team-mate-pro/tmp-standards/definitions/tests/TEST-001-unified-phpunit-structure.sh` |

## Definition

Every TMP PHP project must have a unified test structure with:
1. `phpunit.xml` (or `phpunit.xml.dist`) defining test suites
2. Composer scripts for running each test suite independently
3. Conditional warmup using `team-mate-pro/tests-bundle`
4. Makefile aliases for common test commands

## Required Composer Scripts

### Warmup Scripts (Conditional Execution)

Use `team-mate-pro/tests-bundle` for conditional migrations and fixtures:

| Script | Description |
|--------|-------------|
| `test:warmup:migrate` | Run migrations only if `./migrations` changed |
| `test:warmup:fixtures` | Load fixtures only if `./tests/_Data/DataFixtures` changed |
| `test:warmup` | Execute both warmup scripts |

### Test Execution Scripts

Each test suite defined in `phpunit.xml` **must** have a corresponding composer script:

| Script | Description |
|--------|-------------|
| `tests` | Run all tests (includes warmup) |
| `tests:unit` | Run unit tests only |
| `tests:integration` | Run integration tests (includes warmup) |
| `tests:application` | Run application/API tests (includes warmup) |

### Optional Scripts

| Script | Description |
|--------|-------------|
| `tests:integration:fast` | Run integration tests with `@group fast` (no fixtures) |
| `tests:integration:wip` | Run integration tests with `@group wip` |
| `tests:acceptance` | Run acceptance tests |
| `tests:functional` | Run all functional tests (integration + application) |
| `tests:coverage` | Run tests with code coverage |

## Correct Usage

### phpunit.xml

```xml
<testsuites>
    <testsuite name="unit">
        <directory>tests/Unit</directory>
    </testsuite>
    <testsuite name="integration">
        <directory>tests/Integration</directory>
    </testsuite>
    <testsuite name="application">
        <directory>tests/Application</directory>
    </testsuite>
    <testsuite name="acceptance">
        <directory>tests/Acceptance</directory>
    </testsuite>
</testsuites>
```

### composer.json scripts

```json
{
  "scripts": {
    "test:warmup:migrate": "APP_ENV=test ./vendor/team-mate-pro/tests-bundle/tools/run-if-modified.sh \"php bin/console doctrine:migrations:migrate --no-interaction --env=test\" ./migrations",
    "test:warmup:fixtures": "APP_ENV=test ./vendor/team-mate-pro/tests-bundle/tools/run-if-modified.sh \"php bin/console doctrine:fixtures:load --purger=custom_purger --group=new --no-interaction --env=test\" ./tests/_Data/DataFixtures",
    "test:warmup": [
      "@test:warmup:migrate",
      "@test:warmup:fixtures"
    ],
    "phpunit": "APP_ENV=test ./vendor/bin/phpunit -c phpunit.xml --testdox",
    "tests": [
      "@test:warmup",
      "@phpunit --exclude-group=flaky"
    ],
    "tests:unit": "@phpunit --testsuite unit --exclude-group=flaky",
    "tests:integration": [
      "@test:warmup",
      "@phpunit --testsuite integration --exclude-group=flaky"
    ],
    "tests:integration:fast": "@phpunit --testsuite integration --group=fast --exclude-group=flaky",
    "tests:integration:wip": "@phpunit --testsuite integration --group=wip --exclude-group=flaky",
    "tests:application": [
      "@test:warmup",
      "@phpunit --testsuite application --exclude-group=flaky"
    ],
    "tests:acceptance": [
      "@test:warmup",
      "@phpunit --testsuite acceptance --exclude-group=flaky"
    ]
  }
}
```

### Makefile Aliases

```makefile
## --- Test aliases ---

tests: ### Run all tests
	$(docker-compose) exec -it $(main-container-name) composer tests

tests_unit: ### Run unit tests
	$(docker-compose) exec -it $(main-container-name) composer tests:unit

tests_integration: ### Run integration tests
	$(docker-compose) exec -it $(main-container-name) composer tests:integration

tests_integration_fast: ### Run fast integration tests (no fixtures)
	$(docker-compose) exec -it $(main-container-name) composer tests:integration:fast

tests_application: ### Run application tests
	$(docker-compose) exec -it $(main-container-name) composer tests:application

## --- Short aliases ---

t: tests
tu: tests_unit
ti: tests_integration
tif: tests_integration_fast
ta: tests_application
```

## Violation Examples

### Missing warmup in tests that require fixtures

```json
{
  "scripts": {
    "tests:integration": "@phpunit --testsuite integration"
  }
}
```

**Problem:** Integration tests require database setup but `@test:warmup` is missing.

**Correct:**
```json
{
  "scripts": {
    "tests:integration": [
      "@test:warmup",
      "@phpunit --testsuite integration"
    ]
  }
}
```

### Missing composer script for phpunit testsuite

```xml
<!-- phpunit.xml defines 'acceptance' suite -->
<testsuite name="acceptance">
    <directory>tests/Acceptance</directory>
</testsuite>
```

```json
{
  "scripts": {
    "tests:unit": "...",
    "tests:integration": "..."
    // Missing: tests:acceptance
  }
}
```

**Problem:** Test suite `acceptance` exists in phpunit.xml but has no composer script.

### Not using conditional warmup

```json
{
  "scripts": {
    "test:warmup": [
      "php bin/console doctrine:migrations:migrate --no-interaction --env=test",
      "php bin/console doctrine:fixtures:load --no-interaction --env=test"
    ]
  }
}
```

**Problem:** Migrations and fixtures run every time, even when unchanged.

**Correct:**
```json
{
  "scripts": {
    "test:warmup:migrate": "APP_ENV=test ./vendor/team-mate-pro/tests-bundle/tools/run-if-modified.sh \"php bin/console doctrine:migrations:migrate --no-interaction --env=test\" ./migrations",
    "test:warmup:fixtures": "APP_ENV=test ./vendor/team-mate-pro/tests-bundle/tools/run-if-modified.sh \"php bin/console doctrine:fixtures:load --purger=custom_purger --group=new --no-interaction --env=test\" ./tests/_Data/DataFixtures"
  }
}
```

### Missing Makefile aliases

```makefile
# Only composer scripts, no make aliases
# Developers must remember: composer tests:integration
```

**Correct:** Add `make tests_integration` alias (see Makefile Aliases section).

## Dependencies

This standard requires:
- `team-mate-pro/tests-bundle` - provides `run-if-modified.sh` for conditional execution
- `phpunit/phpunit` - test framework
- `dama/doctrine-test-bundle` - database transaction rollback (recommended)

```bash
composer require --dev team-mate-pro/tests-bundle phpunit/phpunit dama/doctrine-test-bundle
```

## Validation Script

```bash
# From project root
./vendor/team-mate-pro/tmp-standards/definitions/tests/TEST-001-unified-phpunit-structure.sh

# Or specify path
./vendor/team-mate-pro/tmp-standards/definitions/tests/TEST-001-unified-phpunit-structure.sh /path/to/project
```

### Exit Codes

| Code | Meaning |
|------|---------|
| `0` | Project conforms to TEST-001 |
| `1` | Validation failed |

## Rationale

1. **Consistency**: Every project uses the same test commands.
2. **Performance**: Conditional warmup skips unnecessary migrations/fixtures.
3. **Discoverability**: `make help` shows all available test commands.
4. **CI/CD Parity**: Same scripts run locally and in pipelines.
5. **Isolation**: Each test suite can be run independently.
