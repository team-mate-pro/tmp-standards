# UCB-002: UseCase Must Have Invoke Method

**Documentation:** https://github.com/team-mate-pro/tmp-standards/blob/main/definitions/use-case-bundle/UCB-002-use-case-invoke-method.md

## Check Method

| Method | Command |
|--------|---------|
| **PHPSTAN** | `composer phpstan` |

**Rule:** `UseCaseMustHaveInvokeMethodRule` in `phpstan-extension.neon`

## Definition

Every class ending with `UseCase` must have an `__invoke()` method. This ensures a consistent, single-action pattern for all use cases.

This rule is enforced by PHPStan via `UseCaseMustHaveInvokeMethodRule`.

## Exempt Classes

- Abstract UseCase classes (they define shared behavior, not executable actions)

## Correct Usage

```php
final readonly class CreateUserUseCase
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
    ) {
    }

    public function __invoke(CreateUserDtoInterface $dto): Result
    {
        $user = new User($dto->getEmail(), $dto->getName());
        $this->userRepository->save($user);

        return Result::create()->with($user);
    }
}
```

## Violation

```php
// WRONG: Missing __invoke() method
final readonly class CreateUserUseCase
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
    ) {
    }

    public function execute(CreateUserDtoInterface $dto): Result
    {
        // This will fail PHPStan analysis
    }
}
```

PHPStan error:
```
UseCase class "CreateUserUseCase" must have an __invoke method.
```

## Rationale

1. **Single Action Pattern**: Each UseCase represents exactly one business action. The `__invoke()` method makes this explicit and enforces the pattern.

2. **Callable Objects**: Classes with `__invoke()` can be used as callables, enabling functional composition and middleware patterns.

3. **Consistency**: All UseCases share the same interface pattern, making the codebase predictable and easier to navigate.

4. **Framework Integration**: Symfony and other frameworks can automatically invoke UseCase classes as controllers or command handlers when they implement `__invoke()`.
