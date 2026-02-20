# ARCH-001: REST API Route Naming Convention

**Documentation:** https://github.com/team-mate-pro/tmp-standards/blob/main/definitions/architecture/ARCH-001-rest-api-route-naming.md

## Check Method

| Method | Command |
|--------|---------|
| **Manual** | Code review |

## Definition

All REST API routes **must use plural nouns** to represent resources. **Verbs are not allowed** in route paths. HTTP methods (GET, POST, PUT, PATCH, DELETE) determine the action.

## Rules

### 1. Use Plural Nouns Only

Routes must represent **collections** (plural nouns), not actions.

```
# Correct
/api/shops
/api/equipments
/api/facilities
/api/contracts

# Incorrect
/api/shop
/api/createEquipment
/api/checkAvailability
```

### 2. No Verbs in Paths

Actions are expressed through HTTP methods, not URL paths.

| Action | HTTP Method | Route Pattern |
|--------|-------------|---------------|
| List all | GET | `/api/{resources}` |
| Get one | GET | `/api/{resources}/{id}` |
| Create | POST | `/api/{resources}` |
| Replace | PUT | `/api/{resources}/{id}` |
| Update | PATCH | `/api/{resources}/{id}` |
| Delete | DELETE | `/api/{resources}/{id}` |

### 3. State Changes via PATCH or Sub-resources

State transitions (enable, disable, activate) should use:

**Option A: PATCH with payload**
```php
#[Route('/api/shops/{id}', methods: ['PATCH'])]
public function updateShop(UpdateShopRequest $request): JsonResponse
{
    // Request payload: {"enabled": true}
}
```

**Option B: Sub-resource representing state**
```php
#[Route('/api/shops/{id}/status', methods: ['PUT'])]
public function updateShopStatus(UpdateShopStatusRequest $request): JsonResponse
{
    // Request payload: {"status": "enabled"}
}
```

### 4. Sub-resources for Related Collections

```
# Correct
GET  /api/facilities/{id}/availabilities
GET  /api/persons/{id}/equipments
GET  /api/shops/{id}/products

# Incorrect
GET  /api/facilities/check-availability
POST /api/shops/add-product
```

## Generic Endpoints with UseCase Bundle

Routes can be **generic** - the same endpoint may execute different use cases based on **request payload**. This is achieved through:

1. **Single route** with appropriate HTTP method
2. **Request DTO** that implements domain-specific interface
3. **UseCase** receives the interface and executes business logic

### Example: Generic Resource Creation

```php
// Same endpoint, different payloads trigger different validation/logic
#[Route('/api/products', methods: ['POST'])]
public function createProduct(CreateProductRequest $request, CreateProductUseCase $useCase): JsonResponse
{
    return $this->response(($useCase)($request));
}
```

The `CreateProductRequest` implements `CreateProductDtoInterface` - the UseCase determines behavior based on payload content (e.g., product type, marketplace vs custom).

### Example: Generic State Update

```php
// Single endpoint handles multiple state transitions
#[Route('/api/shops/{id}', methods: ['PATCH'])]
public function updateShop(UpdateShopRequest $request, UpdateShopUseCase $useCase): JsonResponse
{
    // Payload {"enabled": true} -> enables shop
    // Payload {"enabled": false} -> disables shop
    // Payload {"name": "New Name"} -> updates name
    return $this->response(($useCase)($request));
}
```

## Conditional UseCase Execution Based on Payload

A single endpoint can conditionally execute **different use cases** based on payload values. The Request DTO implements multiple interfaces and the controller uses `match` expression to select the appropriate use case.

### Pattern: Request Implementing Multiple DTO Interfaces

```php
// Request DTO implements multiple use case interfaces
final class UpdateSuggestionStatusRequest extends AbstractDecoratedRequest
    implements AcceptSuggestionDtoInterface, DismissSuggestionDtoInterface
{
    public ?string $status = null;

    public function isAccepted(): bool
    {
        return $this->status === 'accepted';
    }

    public function isDismissed(): bool
    {
        return $this->status === 'dismissed';
    }
}
```

### Pattern: Controller with Match Expression

```php
#[Route('/api/suggestions/{id}', methods: ['PATCH'])]
public function updateStatus(
    UpdateSuggestionStatusRequest $request,
    AcceptSuggestionUseCase $acceptUseCase,
    DismissSuggestionUseCase $dismissUseCase,
): JsonResponse {
    // Conditional use case execution based on payload value
    $result = match (true) {
        $request->isAccepted() => ($acceptUseCase)($request),
        $request->isDismissed() => ($dismissUseCase)($request),
        default => throw new \InvalidArgumentException('Invalid status'),
    };

    return $this->response($result);
}
```

### Pattern: UseCase with Internal Factory Selection

When creation logic varies by type, use factories inside the use case:

```php
final readonly class CreateTrainingUseCase
{
    public function __construct(
        private IndividualTrainingFactoryInterface $individualFactory,
        private GroupTrainingFactoryInterface $groupFactory,
    ) {}

    public function __invoke(CreateTrainingDtoInterface $dto): Result
    {
        $training = match ($dto->getType()) {
            TrainingType::INDIVIDUAL => $this->individualFactory->create($dto),
            TrainingType::GROUP => $this->groupFactory->create($dto),
        };

        return Result::create(ResultType::SUCCESS_CREATED)->with($training);
    }
}
```

### Key Principles

1. **Request DTO implements multiple interfaces** - enables type-safe injection into different use cases
2. **Helper methods on DTO** - `isAccepted()`, `isDismissed()` make match conditions readable
3. **Match expression in controller** - clear, type-safe selection of use case
4. **Single endpoint** - URL stays RESTful, payload determines behavior

## Correct Usage

```php
class EquipmentController extends AbstractRestApiController
{
    #[Route('/api/equipments', methods: ['GET'])]
    public function list(FindEquipmentsRequest $request): JsonResponse { }

    #[Route('/api/equipments/{id}', methods: ['GET'])]
    public function get(GetEquipmentRequest $request): JsonResponse { }

    #[Route('/api/equipments', methods: ['POST'])]
    public function create(CreateEquipmentRequest $request): JsonResponse { }

    #[Route('/api/equipments/{id}', methods: ['PATCH'])]
    public function update(UpdateEquipmentRequest $request): JsonResponse { }

    #[Route('/api/equipments/{id}', methods: ['DELETE'])]
    public function delete(DeleteEquipmentRequest $request): JsonResponse { }

    // Sub-resource: person's equipments
    #[Route('/api/persons/{personId}/equipments', methods: ['GET'])]
    public function listByPerson(FindPersonEquipmentsRequest $request): JsonResponse { }
}
```

## Violation

```php
// WRONG: Verbs in routes
#[Route('/api/shops/enable', methods: ['POST'])]
public function enableShopAction(): JsonResponse { }

#[Route('/api/shops/disable', methods: ['POST'])]
public function disableShopAction(): JsonResponse { }

#[Route('/api/settlements/{id}/pay', methods: ['POST'])]
public function markSettlementPaidAction(): JsonResponse { }

#[Route('/api/facilities/check-availability', methods: ['GET'])]
public function checkAvailabilityAction(): JsonResponse { }
```

### How to Fix

| Violation | Correct Alternative |
|-----------|---------------------|
| `POST /api/shops/enable` | `PATCH /api/shops/{id}` with `{"enabled": true}` |
| `POST /api/shops/disable` | `PATCH /api/shops/{id}` with `{"enabled": false}` |
| `POST /api/settlements/{id}/pay` | `PATCH /api/settlements/{id}` with `{"status": "paid"}` |
| `GET /api/facilities/check-availability` | `GET /api/facilities/{id}/availabilities` |

## Rationale

1. **Consistency**: Uniform API surface makes the API predictable and easier to learn.

2. **RESTful Semantics**: Resources are nouns; HTTP methods are verbs. Mixing them creates ambiguity (`POST /enable` vs `PUT /status`).

3. **Generic Endpoints**: Payload-driven behavior allows single endpoint to handle multiple use cases while maintaining clean URLs.

4. **Discoverability**: Noun-based routes naturally map to API documentation and client code generation.

5. **Cacheability**: GET requests on noun-based routes are naturally cacheable; verb-based routes break HTTP caching semantics.
