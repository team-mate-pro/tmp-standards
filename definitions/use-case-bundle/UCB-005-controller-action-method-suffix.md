# UCB-005: Controller Action Methods Must Have "Action" Suffix

**Documentation:** https://github.com/team-mate-pro/tmp-standards/blob/main/definitions/use-case-bundle/UCB-005-controller-action-method-suffix.md

## Check Method

| Method | Command |
|--------|---------|
| **PHPStan** | `vendor/bin/phpstan analyse` |
| **Rule class** | `TeamMatePro\TmpStandards\PHPStan\Rules\ControllerActionMethodSuffixRule` |

## Definition

All **public methods** in controllers extending `AbstractRestApiController` (from `team-mate-pro/use-case-bundle`) **must end with the `Action` suffix**.

This convention clearly distinguishes HTTP action methods from helper methods and ensures naming consistency across all modules.

## Why This Matters

### 1. Explicit Intent

The `Action` suffix immediately communicates that a method is an HTTP endpoint handler, not a utility or helper method.

### 2. Consistency Across Modules

Without enforcement, teams mix naming styles — some controllers use `Action` suffix, others don't. This leads to inconsistent codebases that are harder to navigate.

### 3. Framework Convention

Symfony historically uses the `Action` suffix as a convention for controller methods. Following this convention makes the codebase familiar to any Symfony developer.

### 4. Searchability

Grepping for `Action(` instantly finds all HTTP endpoints in the project.

## Correct Usage

```php
final class ShopController extends AbstractRestApiController
{
    #[Route('/api/shops', methods: ['GET'])]
    public function getShopsAction(
        GetShopsRequest $request,
        GetShopsUseCase $useCase,
    ): JsonResponse {
        return $this->response(
            result: $useCase($request),
            serializationGroups: ['shop:list'],
        );
    }

    #[Route('/api/shops/{id}', methods: ['GET'])]
    public function getShopAction(
        GetShopRequest $request,
        GetShopUseCase $useCase,
    ): JsonResponse {
        return $this->response(
            result: $useCase($request),
            serializationGroups: ['shop:read'],
        );
    }
}
```

```php
final class PanovaWebhookController extends AbstractRestApiController
{
    #[Route(path: '/webhooks/v2/offers', methods: ['PUT'])]
    public function createOfferAction(
        PanovaWebhookRequest $request,
        CreateOfferUseCase $useCase,
        Request $httpRequest,
    ): JsonResponse {
        $this->logPayload($httpRequest);

        return $this->response(result: $useCase($request));
    }

    // Private methods are exempt from the rule
    private function logPayload(Request $httpRequest): void
    {
        // ...
    }
}
```

## Violation

```php
// WRONG: Public methods missing "Action" suffix
final class CustomerController extends AbstractRestApiController
{
    #[Route('/api/customers/externals', methods: ['POST'])]
    public function importAllExternalCustomers(  // <-- VIOLATION: should be importAllExternalCustomersAction
        ImportExternalCustomersRequest $request,
        ImportExternalCustomersUseCase $useCase,
    ): JsonResponse {
        return $this->response($useCase($request));
    }

    #[Route('/api/customers/externals/{nip}', methods: ['GET'])]
    public function externalCustomerLookup(  // <-- VIOLATION: should be externalCustomerLookupAction
        GetExternalCustomerLookupRequest $request,
        GetExternalCustomerLookupUseCase $useCase,
    ): JsonResponse {
        return $this->response($useCase($request));
    }
}
```

## Exemptions

The following methods are **exempt** from this rule:

- **Non-public methods** (`private`, `protected`) — helper/utility methods
- **Magic methods** (`__construct`, `__invoke`, etc.)
- **Static methods** — factory methods, configuration helpers

## How to Fix

Rename public methods to include the `Action` suffix:

| Before | After |
|--------|-------|
| `importAllExternalCustomers()` | `importAllExternalCustomersAction()` |
| `externalCustomerLookup()` | `externalCustomerLookupAction()` |
| `getShop()` | `getShopAction()` |
| `createUser()` | `createUserAction()` |

Remember to update any route references if method names are used elsewhere.

## Rationale

1. **Convention over Configuration**: Consistent naming reduces cognitive load
2. **Code Navigation**: Easy to identify action methods in large controllers
3. **Separation of Concerns**: Clear boundary between endpoint handlers and internal helpers
4. **Team Alignment**: All developers follow the same naming pattern
