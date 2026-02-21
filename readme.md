# TMP Standards

A Composer package aggregating coding standards, architectural guidelines, and PHPStan rules for TMP organization.

## Check Methods

Each standard defines HOW it should be verified. Use this table to understand how to check compliance:

```
┌─────────────┬──────────────────────────────────────────────────────────────┐
│ Method      │ How to Check                                                 │
├─────────────┼──────────────────────────────────────────────────────────────┤
│ PHPSTAN     │ composer phpstan                                             │
│             │ Rule configured in phpstan-extension.neon                    │
├─────────────┼──────────────────────────────────────────────────────────────┤
│ SCRIPT      │ ./vendor/team-mate-pro/tmp-standards/definitions/            │
│             │    {category}/{STANDARD_ID}.sh                               │
├─────────────┼──────────────────────────────────────────────────────────────┤
│ AI          │ claude -p "$(cat vendor/team-mate-pro/tmp-standards/         │
│             │    definitions/{category}/{STANDARD_ID}.prompt.txt)"         │
│             │    --cwd .                                                   │
├─────────────┼──────────────────────────────────────────────────────────────┤
│ MANUAL      │ Human review required - see standard definition              │
└─────────────┴──────────────────────────────────────────────────────────────┘
```

## Project Structure

```
tmp-standards/
├── definitions/                    # Standard definitions (markdown + scripts/prompts)
│   ├── clean-code/
│   │   ├── CC-001-no-persist-in-creational-patterns.md
│   │   └── CC-001-no-persist-in-creational-patterns.prompt.txt  # AI prompt
│   ├── design-patterns/
│   │   └── solid/
│   │       ├── SOLID-001-single-responsibility-principle.md
│   │       ├── SOLID-001-single-responsibility-principle.prompt.txt
│   │       ├── SOLID-002-open-closed-principle.md
│   │       ├── SOLID-002-open-closed-principle.prompt.txt
│   │       ├── SOLID-003-liskov-substitution-principle.md
│   │       ├── SOLID-003-liskov-substitution-principle.prompt.txt
│   │       ├── SOLID-004-interface-segregation-principle.md
│   │       ├── SOLID-004-interface-segregation-principle.prompt.txt
│   │       ├── SOLID-005-dependency-inversion-principle.md
│   │       └── SOLID-005-dependency-inversion-principle.prompt.txt
│   ├── infrastructure/
│   │   ├── INF-001-infrastructure-local-makefile.md
│   │   └── INF-001-infrastructure-local-makefile.sh  # Validation script
│   └── use-case-bundle/
│       ├── UCB-001-use-case-abstract-dto.md
│       ├── UCB-002-use-case-invoke-method.md
│       ├── UCB-003-no-auth-in-use-case.md
│       ├── UCB-004-controller-must-use-response-method.md
│       └── UCB-005-controller-action-method-suffix.md
├── src/                            # Source code
│   └── PHPStan/
│       └── Rules/
│           ├── ControllerActionMethodSuffixRule.php
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
| `CC`   | Clean Code - general coding best practices |
| `INF`  | Infrastructure - local development, CI/CD, tooling |
| `SOLID` | Design Patterns - SOLID principles |
| `UCB`  | UseCase Bundle - rules for UseCase pattern |

### Requirements

Each standard definition should clearly specify:
- What it applies to
- Usage examples (correct implementation)
- Violation examples (what to avoid)
- Rationale for the standard

### Available Standards

| Code | Title | Check Method |
|------|-------|--------------|
| [CC-001](definitions/clean-code/CC-001-no-persist-in-creational-patterns.md) | No Persistence in Creational Patterns | AI |
| [INF-001](definitions/infrastructure/INF-001-infrastructure-local-makefile.md) | Local Development Makefile | SCRIPT |
| [SOLID-001](definitions/design-patterns/solid/SOLID-001-single-responsibility-principle.md) | Single Responsibility Principle (SRP) | AI |
| [SOLID-002](definitions/design-patterns/solid/SOLID-002-open-closed-principle.md) | Open/Closed Principle (OCP) | AI |
| [SOLID-003](definitions/design-patterns/solid/SOLID-003-liskov-substitution-principle.md) | Liskov Substitution Principle (LSP) | AI |
| [SOLID-004](definitions/design-patterns/solid/SOLID-004-interface-segregation-principle.md) | Interface Segregation Principle (ISP) | AI |
| [SOLID-005](definitions/design-patterns/solid/SOLID-005-dependency-inversion-principle.md) | Dependency Inversion Principle (DIP) | AI |
| [UCB-001](definitions/use-case-bundle/UCB-001-use-case-abstract-dto.md) | UseCase Parameters Must Be Interfaces | PHPSTAN |
| [UCB-002](definitions/use-case-bundle/UCB-002-use-case-invoke-method.md) | UseCase Must Have Invoke Method | PHPSTAN |
| [UCB-003](definitions/use-case-bundle/UCB-003-no-auth-in-use-case.md) | No Authorization in UseCase Layer | AI |
| [UCB-004](definitions/use-case-bundle/UCB-004-controller-must-use-response-method.md) | Controller Must Use $this->response() | MANUAL |
| [UCB-005](definitions/use-case-bundle/UCB-005-controller-action-method-suffix.md) | Controller Action Methods Must Have "Action" Suffix | PHPSTAN |

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
| `ControllerActionMethodSuffixRule` | `controller.actionMethodSuffix` | [UCB-005](definitions/use-case-bundle/UCB-005-controller-action-method-suffix.md) |

### Disabling Rules

All rules are auto-discovered via `phpstan/extension-installer`. If you need to skip a specific rule in your project, disable it in your `phpstan.neon` using the `ignoreErrors` parameter with the rule identifier:

```neon
parameters:
    ignoreErrors:
        # Disable a rule entirely
        -
            identifier: controller.actionMethodSuffix

        # Disable a rule for specific paths
        -
            identifier: useCase.missingInvoke
            paths:
                - src/Legacy/*

        # Disable a rule with a message pattern
        -
            identifier: useCase.parameterMustBeInterface
            message: '#UseCase "Legacy.*" parameter#'
```

Available rule identifiers:

| Identifier | Rule |
|------------|------|
| `useCase.missingInvoke` | `UseCaseMustHaveInvokeMethodRule` |
| `useCase.parameterMustBeInterface` | `UseCaseParameterMustBeInterfaceRule` |
| `controller.actionMethodSuffix` | `ControllerActionMethodSuffixRule` |

You can also ignore errors inline with PHPStan comments:

```php
/** @phpstan-ignore controller.actionMethodSuffix */
public function legacyEndpoint(): JsonResponse
{
    // ...
}
```

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

#### ControllerActionMethodSuffixRule

Public methods in controllers extending `AbstractRestApiController` must end with the `Action` suffix. Private, protected, static, and magic methods are exempt.

```php
// CORRECT - public methods have "Action" suffix
final class ShopController extends AbstractRestApiController
{
    public function getShopAction(): JsonResponse { /* ... */ }
    public function createShopAction(): JsonResponse { /* ... */ }

    private function logPayload(): void { /* ... */ } // exempt
}

// VIOLATION - missing "Action" suffix
final class CustomerController extends AbstractRestApiController
{
    public function importAllExternalCustomers(): JsonResponse { /* ... */ }
}
```

See [UCB-005](definitions/use-case-bundle/UCB-005-controller-action-method-suffix.md) for detailed documentation.

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
