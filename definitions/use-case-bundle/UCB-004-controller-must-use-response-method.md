# UCB-004: Controller Must Use $this->response() Method

**Documentation:** https://github.com/team-mate-pro/tmp-standards/blob/main/definitions/use-case-bundle/UCB-004-controller-must-use-response-method.md

## Check Method

| Method | Command |
|--------|---------|
| **Manual** | Code review |

## Definition

Controllers extending `AbstractRestApiController` **must use** the `$this->response()` method to return JSON responses. Manual JSON array construction with `$this->json()` is **strictly forbidden**.

This is a **critical anti-pattern** that breaks architectural consistency and must be avoided.

## Why This Matters

### 1. Consistent Response Rendering

The `$this->response()` method provides:
- Unified response envelope structure across all endpoints
- Automatic serialization via JMS Serializer with groups support
- Consistent field naming (snake_case conversion)
- Proper handling of nested objects and collections

### 2. Consistent Status Code Mapping

The `$this->response()` method automatically maps `ResultType` to HTTP status codes:

| ResultType | HTTP Status |
|------------|-------------|
| `SUCCESS` | 200 OK |
| `SUCCESS_CREATED` | 201 Created |
| `FAILURE` | 400 Bad Request |
| `NOT_FOUND` | 404 Not Found |
| `DUPLICATED` | 409 Conflict |
| `FORBIDDEN` | 403 Forbidden |

Manual `$this->json()` requires developers to remember and apply these mappings correctly every time.

### 3. Separation of Concerns

- **UseCase**: Contains business logic, returns `Result<T>` with domain object
- **Controller**: Delegates to UseCase, passes Result to response renderer
- **Serializer**: Transforms domain object to JSON using groups

Manual JSON construction violates this separation by mixing presentation logic into controllers.

## Correct Usage

```php
class QuickActionsController extends AbstractRestApiController
{
    #[Route('/api/me/actions', methods: ['GET'])]
    public function getMyActionsAction(
        GetMyQuickActionsRequest $request,
        GetMyQuickActionsUseCase $useCase,
    ): JsonResponse {
        return $this->response(
            result: $useCase($request),
            serializationGroups: self::getCollectionSerializationGroups(),
        );
    }
}
```

```php
class ShopController extends AbstractRestApiController
{
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

## Violation

```php
// WRONG: Manual JSON array construction - THIS IS FATAL
class ShopController extends AbstractRestApiController
{
    #[Route('/api/shops', methods: ['GET'])]
    public function getShopAction(
        GetShopRequest $request,
        OrganizationShopRepositoryInterface $shopRepository  // Repository in controller!
    ): JsonResponse {
        $organizationId = $request->getOrganizationId();
        $shop = $shopRepository->findByOrganizationId($organizationId);

        if (!$shop) {
            return $this->json([          // WRONG: Manual json()
                'enabled' => false,
                'shop' => null,
            ]);
        }

        return $this->json([              // WRONG: Manual JSON construction
            'enabled' => $shop->isEnabled(),
            'shop' => [
                'id' => $shop->getId(),
                'organization_id' => $shop->getOrganization()->getId(),
                'sylius_channel_code' => $shop->getSyliusChannelCode(),
                'subdomain' => $shop->getSubdomain(),
                // ... 10+ more manual field mappings
            ],
        ]);
    }
}
```

## Problems with Manual JSON Construction

1. **Inconsistent Response Structure**: Different developers create different envelope formats
2. **Duplicated Mapping Logic**: Same entity mapped to array in multiple places
3. **No Serialization Groups**: Cannot control field visibility per endpoint
4. **Status Code Errors**: Developers forget proper HTTP status codes
5. **Maintenance Burden**: Adding a field requires changes in multiple controllers
6. **Testing Complexity**: Cannot test serialization in isolation
7. **Repository in Controller**: Business logic leaks into presentation layer

## How to Refactor

### Step 1: Create UseCase

```php
final readonly class GetShopUseCase
{
    public function __construct(
        private OrganizationShopRepositoryInterface $shopRepository,
    ) {}

    public function __invoke(GetShopDtoInterface $dto): Result
    {
        $shop = $this->shopRepository->findByOrganizationId($dto->getOrganizationId());

        if (!$shop) {
            return Result::create(ResultType::NOT_FOUND, 'Shop not found');
        }

        return Result::create(ResultType::SUCCESS)->with($shop);
    }
}
```

### Step 2: Add Serialization Groups to Entity

```php
class OrganizationShop
{
    #[Groups(['shop:read', 'shop:list'])]
    private string $id;

    #[Groups(['shop:read'])]
    private string $syliusChannelCode;

    // ... other fields with appropriate groups
}
```

### Step 3: Update Controller

```php
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
```

## Exceptions

The only acceptable use of `$this->json()` is for:
- Health check endpoints returning simple status
- Webhook callbacks with externally-defined format
- Legacy endpoints marked for deprecation

Even in these cases, prefer creating a simple DTO with serialization groups.

## Rationale

1. **Architectural Consistency**: All endpoints behave predictably
2. **DRY Principle**: Serialization logic defined once on entities
3. **Testability**: UseCase and serialization tested independently
4. **Maintainability**: Field changes require single-point modification
5. **API Documentation**: Serialization groups integrate with OpenAPI generation
