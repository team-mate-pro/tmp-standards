# UCB-006: Authorization Must Be Handled in Request Class

**Documentation:** https://github.com/team-mate-pro/tmp-standards/blob/main/definitions/use-case-bundle/UCB-006-authorization-in-request.md

## Check Method

| Method | Command |
|--------|---------|
| **AI** | `claude -p "$(cat vendor/team-mate-pro/tmp-standards/definitions/use-case-bundle/UCB-006-authorization-in-request.prompt.txt)" --cwd .` |

## Definition

Authorization checks **must** be implemented in the Request class via the `securityCheck()` method, **not** in the Controller. Controllers extending `AbstractRestApiController` **must not** call `denyAccessUnlessGranted()` or perform manual security checks.

The `securityCheck()` method is automatically called by `AbstractValidatedRequest` during request validation, ensuring authorization happens before the request reaches the controller action.

## Forbidden in Controller

### Method Calls

- `$this->denyAccessUnlessGranted()`
- `$this->security->isGranted()` (use in Request instead)
- `$this->isGranted()`

### Direct Security Access

- Manual permission checks with `Security` service
- `AccessDeniedException` thrown based on permission checks

## Correct Usage

```php
// Request class handles authorization via securityCheck()
final class UpdateSuggestionStatusHttpRequest extends AbstractDecoratedRequest implements AcceptSuggestionDtoInterface, DismissSuggestionDtoInterface
{
    #[NotBlank]
    public string $triggerId;

    public function getTriggerId(): string
    {
        return $this->triggerId;
    }

    // Authorization happens here - called automatically during validation
    protected function securityCheck(): bool
    {
        return $this->security->isGranted(
            Permission::AI_SUGGESTIONS_READ,
            [$this->getOrganizationId()]
        );
    }
}

// Controller is clean - no authorization code
#[Route('/api/ai-suggestions')]
final class AiSuggestionsTriggerController extends AbstractRestApiController
{
    #[Route('/{triggerId}', name: 'ai_suggestions_update_status', methods: ['PATCH'])]
    public function updateStatusAction(
        UpdateSuggestionStatusHttpRequest $request,
        AcceptSuggestionUseCase $acceptUseCase,
        DismissSuggestionUseCase $dismissUseCase,
    ): JsonResponse {
        // No denyAccessUnlessGranted() here!
        // Authorization already happened in Request::securityCheck()

        match (true) {
            $request->isAccepted() => $acceptUseCase($request),
            $request->isDismissed() => $dismissUseCase($request),
            default => throw new \InvalidArgumentException('Invalid status'),
        };

        return new JsonResponse(null, 204);
    }
}
```

## Violation

```php
// WRONG: Authorization in Controller
#[Route('/api/ai-suggestions')]
final class AiSuggestionsTriggerController extends AbstractRestApiController
{
    #[Route('', name: 'ai_suggestions_list', methods: ['GET'])]
    public function listAction(
        GetPendingSuggestionsHttpRequest $request,
        GetPendingSuggestionsUseCase $useCase,
    ): JsonResponse {
        // VIOLATION: Authorization should be in Request::securityCheck()
        $this->denyAccessUnlessGranted(Permission::AI_SUGGESTIONS_READ, [$request->getOrganizationId()]);

        return $this->response($useCase($request));
    }

    #[Route('/{triggerId}', name: 'ai_suggestions_update_status', methods: ['PATCH'])]
    public function updateStatusAction(
        UpdateSuggestionStatusHttpRequest $request,
        AcceptSuggestionUseCase $acceptUseCase,
        DismissSuggestionUseCase $dismissUseCase,
    ): JsonResponse {
        // VIOLATION: This check belongs in UpdateSuggestionStatusHttpRequest::securityCheck()
        $this->denyAccessUnlessGranted(Permission::AI_SUGGESTIONS_READ, [$request->getOrganizationId()]);

        match (true) {
            $request->isAccepted() => $acceptUseCase($request),
            $request->isDismissed() => $dismissUseCase($request),
            default => throw new \InvalidArgumentException('Invalid status'),
        };

        return new JsonResponse(null, 204);
    }
}
```

```php
// WRONG: Request class missing securityCheck()
final class GetPendingSuggestionsHttpRequest extends AbstractDecoratedRequest implements GetPendingSuggestionsDtoInterface
{
    public function getOrganizationId(): string
    {
        return parent::getOrganizationId();
    }

    // VIOLATION: Missing securityCheck() method
    // Authorization is done in Controller instead
}
```

## securityCheck() Method Pattern

The `securityCheck()` method must:

1. Return `bool` - `true` if access is granted, `false` if denied
2. Use `$this->security->isGranted()` with appropriate Permission constant
3. Pass required subjects (organization ID, resource IDs, team IDs, etc.)

### Simple Permission Check

```php
protected function securityCheck(): bool
{
    return $this->security->isGranted(
        Permission::AI_SUGGESTIONS_READ,
        [$this->getOrganizationId()]
    );
}
```

### Permission with Resource ID

```php
protected function securityCheck(): bool
{
    return $this->security->isGranted(
        Permission::CONTRACT_ITEM_READ,
        [$this->getOrganizationId(), $this->getId()]
    );
}
```

### Conditional Permission Check

```php
protected function securityCheck(): bool
{
    if ($this->hard) {
        return $this->security->isGranted(Permission::TRAINING_ITEM_HARD_REMOVE);
    }

    return $this->security->isGranted(
        Permission::TRAINING_ITEM_REMOVE,
        [$this->getOrganizationId(), $this->getTeamId(), $this->getTrainingId()]
    );
}
```

### Permission with Team Scope

```php
protected function securityCheck(): bool
{
    return $this->security->isGranted(
        Permission::PLAYER_ITEM_READ,
        [$this->getOrganizationId(), $this->getTeamId(), $this->getPlayerId()]
    );
}
```

## Relation to Other Rules

This rule complements:

- **UCB-003**: No Authorization in UseCase - authorization must not be in UseCase layer
- **UCB-006**: Authorization in Request - authorization must be in Request layer (this rule)

Together they establish the authorization boundary:
- **Request** (presentation layer): Handles authorization via `securityCheck()`
- **Controller** (presentation layer): Delegates to Request and UseCase, no security checks
- **UseCase** (application layer): Pure business logic, no security concerns

## Why Request Layer?

1. **Early Rejection**: Invalid requests are rejected before reaching business logic
2. **Single Responsibility**: Request handles validation AND authorization
3. **Testability**: Authorization can be tested in isolation with Request class
4. **Consistency**: All security checks follow the same pattern
5. **Framework Integration**: `AbstractValidatedRequest` calls `securityCheck()` automatically
6. **Clean Controllers**: Controllers focus on orchestration, not security

## Rationale

1. **Separation of Concerns**: Controller orchestrates, Request validates and authorizes
2. **DRY Principle**: Authorization logic is defined once per request type
3. **Automatic Enforcement**: Framework calls `securityCheck()` - developers can't forget
4. **Explicit Dependencies**: Request has access to all data needed for authorization
5. **Audit Trail**: All authorization decisions happen in a predictable location
6. **Maintainability**: Changing permission logic requires editing one file (Request)

## Exception

The only acceptable use of security checks in Controller is for:
- Endpoints that use `#[IsGranted]` attribute for simple role-based access (e.g., `#[IsGranted('ROLE_ADMIN')]`)
- These are class-level or method-level attributes, not inline checks

Even then, prefer moving complex permission logic to Request's `securityCheck()` method.
