# TEST-003: Use Case DTO Classes for Testing

**Documentation:** https://github.com/team-mate-pro/tmp-standards/blob/main/definitions/tests/TEST-003-use-case-dto-for-tests.md

## Definition

Every Use Case that accepts a DTO interface must have:
1. A **DTO interface** (`*DtoInterface`) defining the contract
2. A **concrete DTO class** (`*Dto`) implementing the interface for use in tests
3. The use case `__invoke` method must type-hint the **interface**, not the concrete class

This enables testing use cases with simple DTO instantiation while maintaining the interface contract.

## Required Structure

```
src/UseCase/{UseCaseName}/
├── {UseCaseName}DtoInterface.php   # Interface defining the contract
├── {UseCaseName}Dto.php            # Simple concrete implementation
├── {UseCaseName}UseCase.php        # Use case accepting the interface
└── {UseCaseName}Request.php        # Optional: validated HTTP request (also implements interface)
```

## Correct Usage

### DTO Interface

```php
<?php

declare(strict_types=1);

namespace App\UseCase\CancelService;

interface CancelServiceDtoInterface
{
    public function getChargeItemId(): int;

    public function getCompanyId(): int;
}
```

### Concrete DTO (for tests)

```php
<?php

declare(strict_types=1);

namespace App\UseCase\CancelService;

final readonly class CancelServiceDto implements CancelServiceDtoInterface
{
    public function __construct(
        private int $chargeItemId,
        private int $companyId,
    ) {
    }

    public function getChargeItemId(): int
    {
        return $this->chargeItemId;
    }

    public function getCompanyId(): int
    {
        return $this->companyId;
    }
}
```

### Use Case (accepts interface)

```php
<?php

declare(strict_types=1);

namespace App\UseCase\CancelService;

final readonly class CancelServiceUseCase
{
    public function __invoke(CancelServiceDtoInterface $dto): Result
    {
        // Implementation uses interface methods
        $chargeItemId = $dto->getChargeItemId();
        // ...
    }
}
```

### Unit Test

```php
<?php

declare(strict_types=1);

namespace App\Tests\Unit\UseCase\CancelService;

use App\UseCase\CancelService\CancelServiceDto;
use App\UseCase\CancelService\CancelServiceUseCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class CancelServiceUseCaseTest extends TestCase
{
    #[Test]
    public function returnsSuccessWhenCancellationSucceeds(): void
    {
        $useCase = new CancelServiceUseCase($this->createPpmApi());

        // Use concrete DTO for simple instantiation in tests
        $result = ($useCase)(new CancelServiceDto(123, 1));

        $this->assertSame(ResultType::SUCCESS, $result->getType());
    }
}
```

## Violation Examples

### Anonymous class in test

```php
// WRONG: Anonymous class in test file
private function createDto(int $chargeItemId): CancelServiceDtoInterface
{
    return new class($chargeItemId) implements CancelServiceDtoInterface {
        public function __construct(private readonly int $chargeItemId) {}
        public function getChargeItemId(): int { return $this->chargeItemId; }
        public function getCompanyId(): int { return 1; }
    };
}
```

**Problem:** Anonymous classes make tests harder to read and maintain.

**Correct:** Create a concrete `CancelServiceDto` class in `src/UseCase/CancelService/`.

### Use case accepts concrete class instead of interface

```php
// WRONG: Concrete class in signature
public function __invoke(CancelServiceDto $dto): Result
```

**Problem:** Couples use case to concrete implementation, reduces flexibility.

**Correct:**
```php
public function __invoke(CancelServiceDtoInterface $dto): Result
```

### Missing concrete DTO class

```
src/UseCase/CancelService/
├── CancelServiceDtoInterface.php
├── CancelServiceUseCase.php
└── CancelServiceRequest.php    # Only HTTP request, no simple DTO
```

**Problem:** Tests must either mock the interface or create anonymous classes.

**Correct:** Add `CancelServiceDto.php` for test instantiation.

## Test Helper Classes Location

If a test requires custom implementations (e.g., test doubles, exceptions), place them in:

```
tests/_Data/TestDouble/
```

Example:
```
tests/_Data/TestDouble/TestHttpException.php
```

**Do NOT** define multiple classes in a single test file.

## Rationale

1. **Simplicity**: Tests use `new CancelServiceDto(123, 1)` instead of anonymous classes
2. **Readability**: DTO instantiation is clear and self-documenting
3. **Flexibility**: Use case accepts interface, allowing different implementations
4. **Separation**: HTTP validation stays in Request class, use case logic uses interface
5. **Reusability**: Same DTO can be used across multiple tests

## Dependencies

This standard works with:
- `team-mate-pro/use-case-bundle` - provides validated request patterns
- PHPUnit 11+ with `#[Test]` attributes
