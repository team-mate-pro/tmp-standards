# CC-002: Fail Fast

**Documentation:** https://github.com/team-mate-pro/tmp-standards/blob/main/definitions/clean-code/CC-002-fail-fast.md

## Check Method

| Method | Command |
|--------|---------|
| **AI** | `claude -p "$(cat vendor/team-mate-pro/tmp-standards/definitions/clean-code/CC-002-fail-fast.prompt.txt)" --cwd .` |

## Definition

Methods and functions must validate preconditions at the very beginning and fail immediately when invariants are violated. Invalid state must never propagate deeper into the call stack. This principle, described by Martin Fowler, means: **detect problems as early as possible and stop execution immediately** rather than continuing with invalid data and failing in obscure ways later.

## Applies To

- UseCase classes (`*UseCase`)
- Service classes (`*Service`)
- Factory classes (`*Factory`)
- Domain entity methods
- Any method that accepts input requiring validation

## Rules

### CC-002.1: Guard Clauses at the Top

All precondition checks must appear at the beginning of a method, before any business logic. Guard clauses must use early returns or throw exceptions — never nest business logic inside validation conditions.

### CC-002.2: No Silent Failures

Methods must not silently ignore invalid input by returning `null`, empty collections, or default values. If input is invalid, the method must throw an exception or return an explicit error result.

### CC-002.3: Validate at the Boundary

Input validation must happen at the entry point (UseCase, Controller, Request) — not deep inside domain services or repositories. By the time data reaches inner layers, it should already be validated.

### CC-002.4: Specific Exceptions

Throw specific, descriptive exceptions rather than generic ones. The exception message must clearly state what went wrong and what was expected.

### CC-002.5: No Defensive Deep Nesting

Do not wrap entire method bodies in `if ($x !== null) { if ($y !== null) { ... } }` chains. Use guard clauses to eliminate invalid states early, keeping the main logic at the top indentation level.

## Correct Usage

### Guard Clauses in UseCase

```php
final readonly class AssignPlayerToTeamUseCase
{
    public function __construct(
        private PlayerRepositoryInterface $playerRepository,
        private TeamRepositoryInterface $teamRepository,
    ) {}

    public function __invoke(AssignPlayerDtoInterface $dto): Result
    {
        // Fail fast: validate all preconditions first
        $player = $this->playerRepository->find($dto->getPlayerId());
        if ($player === null) {
            return Result::create(ResultType::NOT_FOUND, 'Player not found');
        }

        $team = $this->teamRepository->find($dto->getTeamId());
        if ($team === null) {
            return Result::create(ResultType::NOT_FOUND, 'Team not found');
        }

        if ($team->isFull()) {
            return Result::create(ResultType::FAILURE, 'Team roster is full');
        }

        if ($player->hasActiveContract()) {
            return Result::create(ResultType::FAILURE, 'Player already has an active contract');
        }

        // All preconditions passed — execute business logic
        $player->assignTo($team);
        $this->playerRepository->save($player);

        return Result::create(ResultType::SUCCESS)->with($player);
    }
}
```

### Guard Clauses in Domain Entity

```php
final class Invoice
{
    public function markAsPaid(\DateTimeImmutable $paidAt): void
    {
        // Fail fast: reject invalid state transitions
        if ($this->status === InvoiceStatus::CANCELLED) {
            throw new \DomainException(
                sprintf('Cannot mark cancelled invoice #%s as paid', $this->number)
            );
        }

        if ($this->status === InvoiceStatus::PAID) {
            throw new \DomainException(
                sprintf('Invoice #%s is already paid', $this->number)
            );
        }

        if ($paidAt < $this->issuedAt) {
            throw new \InvalidArgumentException(
                sprintf('Payment date cannot be before issue date for invoice #%s', $this->number)
            );
        }

        // All guards passed — apply state change
        $this->status = InvoiceStatus::PAID;
        $this->paidAt = $paidAt;
    }
}
```

### Guard Clauses in Factory

```php
final readonly class DiscountFactory
{
    public function create(string $code, float $percentage, \DateTimeImmutable $expiresAt): Discount
    {
        // Fail fast: validate invariants
        if ($percentage <= 0 || $percentage > 100) {
            throw new \InvalidArgumentException(
                sprintf('Discount percentage must be between 0 and 100, got %s', $percentage)
            );
        }

        if ($expiresAt <= new \DateTimeImmutable()) {
            throw new \InvalidArgumentException('Discount expiration date must be in the future');
        }

        if (trim($code) === '') {
            throw new \InvalidArgumentException('Discount code cannot be empty');
        }

        return new Discount(
            id: Uuid::v4(),
            code: strtoupper(trim($code)),
            percentage: $percentage,
            expiresAt: $expiresAt,
        );
    }
}
```

### Early Return in Service

```php
final readonly class NotificationService
{
    public function sendOrderConfirmation(Order $order): void
    {
        // Fail fast: nothing to do for draft orders
        if ($order->isDraft()) {
            return;
        }

        // Fail fast: customer must have an email
        $email = $order->getCustomer()->getEmail();
        if ($email === null) {
            throw new \RuntimeException(
                sprintf('Customer for order #%s has no email address', $order->getNumber())
            );
        }

        // All checks passed — send notification
        $this->mailer->send(
            to: $email,
            template: 'order_confirmation',
            context: ['order' => $order],
        );
    }
}
```

## Violation

### Deep Nesting Instead of Guard Clauses

```php
// WRONG: Business logic buried inside nested conditions
final readonly class AssignPlayerToTeamUseCase
{
    public function __invoke(AssignPlayerDtoInterface $dto): Result
    {
        $player = $this->playerRepository->find($dto->getPlayerId());
        if ($player !== null) {                                          // ❌ Deep nesting
            $team = $this->teamRepository->find($dto->getTeamId());
            if ($team !== null) {                                        // ❌ Deeper nesting
                if (!$team->isFull()) {                                  // ❌ Even deeper
                    if (!$player->hasActiveContract()) {                 // ❌ 4 levels deep
                        $player->assignTo($team);
                        $this->playerRepository->save($player);
                        return Result::create(ResultType::SUCCESS)->with($player);
                    } else {
                        return Result::create(ResultType::FAILURE, 'Player has contract');
                    }
                } else {
                    return Result::create(ResultType::FAILURE, 'Team is full');
                }
            } else {
                return Result::create(ResultType::NOT_FOUND, 'Team not found');
            }
        } else {
            return Result::create(ResultType::NOT_FOUND, 'Player not found');
        }
    }
}
```

**Problems:**
- Business logic is at indentation level 4 — hard to find and read
- Error cases are scattered at the bottom of each `else` block
- Adding new preconditions increases nesting further
- Reading the "happy path" requires mentally skipping all the conditions

### Silent Failure — Returning Null/Default

```php
// WRONG: Silently returns null instead of failing
final readonly class GetPlayerUseCase
{
    public function __invoke(GetPlayerDtoInterface $dto): ?Player
    {
        $player = $this->playerRepository->find($dto->getPlayerId());

        if ($player === null) {
            return null;  // ❌ Silent failure — caller has no idea why it's null
        }

        if (!$player->isActive()) {
            return null;  // ❌ Same null for completely different reason
        }

        return $player;
    }
}
```

**Problems:**
- Caller cannot distinguish "not found" from "inactive"
- Null propagates through the call stack, causing NullPointerException elsewhere
- Debugging requires tracing backwards to find where null originated

**Correct:**
```php
public function __invoke(GetPlayerDtoInterface $dto): Result
{
    $player = $this->playerRepository->find($dto->getPlayerId());

    if ($player === null) {
        return Result::create(ResultType::NOT_FOUND, 'Player not found');
    }

    if (!$player->isActive()) {
        return Result::create(ResultType::FAILURE, 'Player is inactive');
    }

    return Result::create(ResultType::SUCCESS)->with($player);
}
```

### Late Validation — Checking Too Deep

```php
// WRONG: Validation happens inside repository/infrastructure layer
final readonly class PlayerRepository
{
    public function assignToTeam(string $playerId, string $teamId): void
    {
        $player = $this->em->find(Player::class, $playerId);

        // ❌ Business validation in repository
        if ($player === null) {
            throw new \RuntimeException('Player not found');
        }

        $team = $this->em->find(Team::class, $teamId);

        // ❌ Business validation in repository
        if ($team->isFull()) {
            throw new \RuntimeException('Team is full');
        }

        $player->setTeam($team);
        $this->em->flush();
    }
}
```

**Problems:**
- Repository handles business validation that belongs in UseCase
- Mixing persistence concerns with business rules
- Harder to test — requires database mocking for business rule tests
- Error messages are generic `RuntimeException` instead of domain-specific

### Generic Exceptions

```php
// WRONG: Generic exception with no context
if ($order === null) {
    throw new \Exception('Error');  // ❌ What error? Which order? Why?
}

if ($amount < 0) {
    throw new \RuntimeException('Invalid');  // ❌ What is invalid?
}
```

**Correct:**
```php
if ($order === null) {
    throw new OrderNotFoundException(
        sprintf('Order with ID "%s" not found', $orderId)
    );
}

if ($amount < 0) {
    throw new \InvalidArgumentException(
        sprintf('Order amount must be positive, got %s', $amount)
    );
}
```

### Validation After Side Effects

```php
// WRONG: Side effect happens before validation
final readonly class TransferFundsUseCase
{
    public function __invoke(TransferFundsDtoInterface $dto): Result
    {
        $source = $this->accountRepository->find($dto->getSourceAccountId());
        $target = $this->accountRepository->find($dto->getTargetAccountId());

        // ❌ Side effect BEFORE validation
        $source->debit($dto->getAmount());
        $this->accountRepository->save($source);

        // ❌ Validation AFTER money was already debited
        if ($target === null) {
            // Money was debited but target doesn't exist!
            return Result::create(ResultType::NOT_FOUND, 'Target account not found');
        }

        $target->credit($dto->getAmount());
        $this->accountRepository->save($target);

        return Result::create(ResultType::SUCCESS);
    }
}
```

**Correct:**
```php
public function __invoke(TransferFundsDtoInterface $dto): Result
{
    // Fail fast: validate ALL preconditions before ANY side effects
    $source = $this->accountRepository->find($dto->getSourceAccountId());
    if ($source === null) {
        return Result::create(ResultType::NOT_FOUND, 'Source account not found');
    }

    $target = $this->accountRepository->find($dto->getTargetAccountId());
    if ($target === null) {
        return Result::create(ResultType::NOT_FOUND, 'Target account not found');
    }

    if ($source->getBalance() < $dto->getAmount()) {
        return Result::create(ResultType::FAILURE, 'Insufficient funds');
    }

    // All preconditions met — execute transfer
    $source->debit($dto->getAmount());
    $target->credit($dto->getAmount());

    $this->accountRepository->save($source);
    $this->accountRepository->save($target);

    return Result::create(ResultType::SUCCESS);
}
```

## Rationale

1. **Debuggability**: When a method fails at the entry point with a clear message, developers immediately know what went wrong. Failures deep inside nested logic require tracing through multiple layers.

2. **Readability**: Guard clauses keep the "happy path" at the top indentation level, making it easy to scan the method and understand what it does.

3. **Safety**: Validating before side effects prevents partial operations — money debited but not credited, entities half-modified, events dispatched for invalid state.

4. **Maintainability**: Adding a new precondition is a single guard clause at the top — not another layer of nesting around the entire method body.

5. **Testability**: Each guard clause maps to one test case. Testing `if player not found → NOT_FOUND` is trivial. Testing nested conditions requires complex setup to reach the inner branch.

6. **Error Specificity**: Specific exceptions at the boundary make monitoring, alerting, and log analysis straightforward. Generic exceptions from deep inside the stack are noise.

## Reference

- Martin Fowler: [Fail Fast](https://www.martinfowler.com/ieeeSoftware/failFast.pdf)
- Martin Fowler: [Replace Nested Conditional with Guard Clauses](https://refactoring.com/catalog/replaceNestedConditionalWithGuardClauses.html)
