# UCB-001: UseCase Parameters Must Be Interfaces

## Check Method

| Method | Command |
|--------|---------|
| **PHPSTAN** | `composer phpstan` |

**Rule:** `UseCaseParameterMustBeInterfaceRule` in `phpstan-extension.neon`

## Definition

Every UseCase class `__invoke()` method must accept **interfaces** or **scalar types** as parameters. Concrete classes (Command, Request, DTO) are **not allowed** as parameter types.

This rule is enforced by PHPStan via `UseCaseParameterMustBeInterfaceRule`.

## Allowed Parameter Types

- Interfaces (e.g., `EnableShopDtoInterface`)
- Scalar types (e.g., `string`, `int`, `bool`, `float`)
- Nullable scalars (e.g., `?string`)
- Built-in types (`array`, `mixed`, `callable`, `iterable`)

## Correct Usage

```php
// 1. Define interface for the DTO
interface EnableShopDtoInterface
{
    public function getOrganizationId(): string;
    public function getSubdomain(): string;
    public function getShopName(): ?string;
}

// 2. Implement interface in concrete class (Request, Command, etc.)
final class EnableShopRequest extends AbstractDecoratedRequest implements EnableShopDtoInterface
{
    public string $subdomain;
    public ?string $shop_name = null;

    public function getSubdomain(): string
    {
        return $this->subdomain;
    }

    public function getShopName(): ?string
    {
        return $this->shop_name;
    }
}

// 3. UseCase accepts interface, not concrete class
final readonly class EnableShopUseCase
{
    public function __invoke(EnableShopDtoInterface $request): Result
    {
        // Access data via interface methods
        $subdomain = $request->getSubdomain();
    }
}
```

## Violation

```php
// WRONG: Concrete class as parameter type
final readonly class EnableShopUseCase
{
    public function __invoke(EnableShopRequest $request): Result
    {
        // This will fail PHPStan analysis
    }
}
```

PHPStan error:
```
UseCase "EnableShopUseCase" parameter $request must use an interface, not concrete class "EnableShopRequest".
```

## File Structure

```
Modules/{Module}/UseCase/{UseCaseName}/
├── {UseCaseName}UseCase.php           # The UseCase class
├── {UseCaseName}DtoInterface.php      # Interface for the DTO
└── {UseCaseName}Request.php           # Concrete implementation (HTTP request)
```

## Rationale

1. **Loose Coupling**: UseCase depends on abstraction, not implementation. Controllers, CLI commands, and tests can provide different implementations of the same interface.

2. **Testability**: Unit tests can easily create mock or stub implementations of the interface without depending on framework-specific request classes.

3. **Single Responsibility**: The interface defines the contract (what data is needed), while implementations handle how that data is obtained (HTTP request, CLI input, message queue payload).

4. **PHPStan Compliance**: The `UseCaseParameterMustBeInterfaceRule` enforces this standard at static analysis time, preventing violations from reaching production.
