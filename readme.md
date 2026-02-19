# TMP Standards

A Composer package aggregating coding standards, architectural guidelines, and PHPStan rules for TMP organization.

## Project Structure

```
tmp-standards/
├── definitions/                    # Standard definitions (markdown + scripts)
│   ├── infrastructure/
│   │   ├── INF-001-infrastructure-local-makefile.md
│   │   └── INF-001-infrastructure-local-makefile.sh  # Validation script
│   └── use-case-bundle/
│       ├── UCB-001-use-case-abstract-dto.md
│       └── UCB-002-use-case-invoke-method.md
├── src/                            # Source code
│   └── PHPStan/
│       └── Rules/
│           ├── UseCaseMustHaveInvokeMethodRule.php
│           └── UseCaseParameterMustBeInterfaceRule.php
├── tests/                          # PHPUnit tests
│   ├── _Data/
│   │   └── Fixtures/               # Test fixtures
│   │       └── UseCase/
│   └── PHPStan/
│       └── Rules/                  # Rule tests
├── composer.json
├── phpstan-extension.neon          # PHPStan extension config
├── phpstan.neon                    # PHPStan config for this package
├── phpunit.xml.dist                # PHPUnit config
└── readme.md
```

## Standard Definitions

### Naming Convention

Each standard must follow the format: `{PREFIX}-{NUMBER}`, e.g., `UCB-001` (UseCase Bundle).
The standard name can be extended with a longer description.

### Prefixes

| Prefix | Category |
|--------|----------|
| `INF`  | Infrastructure - local development, CI/CD, tooling |
| `UCB`  | UseCase Bundle - rules for UseCase pattern |

### Requirements

Each standard definition should clearly specify:
- What it applies to
- Usage examples (correct implementation)
- Violation examples (what to avoid)
- Rationale for the standard

### Available Standards

| Code | Title | Description |
|------|-------|-------------|
| [INF-001](definitions/infrastructure/INF-001-infrastructure-local-makefile.md) | Local Development Makefile | Required Makefile commands: `start`, `stop`, `fast`, `check` |
| [UCB-001](definitions/use-case-bundle/UCB-001-use-case-abstract-dto.md) | UseCase Parameters Must Be Interfaces | UseCase `__invoke()` parameters must be interfaces or scalar types |
| [UCB-002](definitions/use-case-bundle/UCB-002-use-case-invoke-method.md) | UseCase Must Have Invoke Method | Every UseCase class must have an `__invoke()` method |

## Validation Scripts

Some standards include validation scripts that can be run to check project compliance.

### INF-001: Makefile Validator

Validates that your project's Makefile conforms to INF-001 standard.

```bash
# Run from project root
./vendor/team-mate-pro/tmp-standards/definitions/infrastructure/INF-001-infrastructure-local-makefile.sh

# Or specify a different path
./vendor/team-mate-pro/tmp-standards/definitions/infrastructure/INF-001-infrastructure-local-makefile.sh /path/to/project
```

**What it checks:**
- Required targets: `start`, `stop`, `fast`, `check`
- Recommended targets: `help`, `check_fast`, `fix`, `tests`
- Required variables: `docker-compose`, `main-container-name`, `vendor-dir`
- Optional include syntax (`-include`)
- Self-documenting `###` comments
- Common aliases (`c`, `cf`, `f`, `t`)

**Exit codes:** `0` = passed, `1` = failed

## PHPStan

This package provides PHPStan rules that enforce TMP coding standards.

### Installation

Add the package to your project using Composer:

```bash
composer require team-mate-pro/tmp-standards --dev
```

For private repository, add to your `composer.json`:

```json
{
    "repositories": [
        {
            "type": "vcs",
            "url": "git@github.com:team-mate-pro/tmp-standards.git"
        }
    ]
}
```

### Configuration

The PHPStan rules are auto-discovered via the `phpstan/extension-installer`. If you have it installed, no additional configuration is needed.

If you don't use the extension installer, include the extension manually in your `phpstan.neon`:

```neon
includes:
    - vendor/team-mate-pro/tmp-standards/phpstan-extension.neon
```

### Available Rules

| Rule | Identifier | Standard |
|------|------------|----------|
| `UseCaseMustHaveInvokeMethodRule` | `useCase.missingInvoke` | [UCB-002](definitions/use-case-bundle/UCB-002-use-case-invoke-method.md) |
| `UseCaseParameterMustBeInterfaceRule` | `useCase.parameterMustBeInterface` | [UCB-001](definitions/use-case-bundle/UCB-001-use-case-abstract-dto.md) |

### Rule Details

#### UseCaseMustHaveInvokeMethodRule

Every class ending with `UseCase` must have an `__invoke()` method. Abstract classes are exempt.

```php
// CORRECT
final readonly class CreateUserUseCase
{
    public function __invoke(CreateUserDtoInterface $dto): Result
    {
        // ...
    }
}

// VIOLATION - missing __invoke()
final readonly class CreateUserUseCase
{
    public function execute(CreateUserDtoInterface $dto): Result
    {
        // ...
    }
}
```

See [UCB-002](definitions/use-case-bundle/UCB-002-use-case-invoke-method.md) for detailed documentation.

#### UseCaseParameterMustBeInterfaceRule

UseCase `__invoke()` method parameters must be **interfaces** or **scalar types**. Concrete classes are not allowed.

```php
// CORRECT - interface parameter
public function __invoke(CreateUserDtoInterface $dto): Result

// CORRECT - scalar parameters
public function __invoke(string $userId, int $limit): Result

// VIOLATION - concrete class parameter
public function __invoke(CreateUserRequest $request): Result
```

See [UCB-001](definitions/use-case-bundle/UCB-001-use-case-abstract-dto.md) for detailed documentation.

## Development

### Requirements

- PHP 8.3+
- Composer

### Setup

```bash
composer install
```

### Running Tests

```bash
composer test
```

### Running PHPStan

```bash
vendor/bin/phpstan analyse
```

## License

Proprietary - Team Mate Pro
