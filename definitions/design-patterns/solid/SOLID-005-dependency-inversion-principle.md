# SOLID-005: Dependency Inversion Principle (DIP)

**Documentation:** https://github.com/team-mate-pro/tmp-standards/blob/main/definitions/design-patterns/solid/SOLID-005-dependency-inversion-principle.md

## Check Method

| Method | Command |
|--------|---------|
| **AI** | `claude -p "$(cat vendor/team-mate-pro/tmp-standards/definitions/design-patterns/solid/SOLID-005-dependency-inversion-principle.prompt.txt)" --cwd .` |

## Definition

High-level modules should **not depend on low-level modules**. Both should depend on abstractions (interfaces). Additionally, abstractions should not depend on details — details should depend on abstractions. Classes should receive their dependencies via constructor injection using interfaces, not concrete classes.

## Applies To

- Service classes
- Controller classes
- Command classes
- Any class with constructor dependencies

## Correct Usage

```php
// High-level module depends on abstraction
final readonly class SyncOrderUseCase
{
    public function __construct(
        private ExternalInvoiceClientInterface $invoiceClient, // Abstraction
        private OrderRepositoryInterface $orderRepository,     // Abstraction
        private LoggerInterface $logger,                       // Abstraction
    ) {
    }

    public function __invoke(SyncOrderDtoInterface $dto): Result
    {
        $order = $this->orderRepository->findOrFail($dto->getOrderId());
        $this->invoiceClient->sync($order);

        return Result::success();
    }
}

// Low-level module implements the abstraction
final readonly class FakturowniaClient implements ExternalInvoiceClientInterface
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private string $apiToken,
        private string $baseUrl,
    ) {
    }

    public function sync(Order $order): void
    {
        // Fakturownia-specific implementation
    }
}
```

```php
// Controller depends on abstractions, not concrete services
final class OrderFileController extends AbstractController
{
    public function __construct(
        private readonly OrderRepositoryInterface $orderRepository, // Interface
        private readonly FileStorageInterface $fileStorage,         // Interface
        private readonly NotificationServiceInterface $notifier,    // Interface
    ) {
    }
}
```

## Violation

**Real example from `gate-backend`:**

`src/Controller/Api/OrderFileController.php` - depends on concrete classes instead of interfaces:

```php
// VIOLATION: Controller depends on concrete repository and service implementations
class OrderFileController extends AbstractController
{
    public function __construct(
        private readonly OrderRepository $orderRepository,       // Concrete class!
        private readonly EntityManagerInterface $entityManager,   // Uses EntityManager directly
        private readonly NormalizerInterface $normalizer,
        private readonly EmailService $emailService,             // Concrete class!
        #[Autowire(service: 'storage.filesystem')]
        private readonly FilesystemOperator $storageFilesystem,
    ) {
    }
}
```

Further in the controller, EntityManager is used to fetch related entities directly:

```php
// VIOLATION: Bypassing repository abstraction, using EntityManager directly
$orderFiles = $this->entityManager->getRepository(OrderFile::class)
    ->findBy(['order' => $order]);
```

**Why it violates DIP:** The controller (high-level) directly depends on `OrderRepository` (concrete low-level) instead of `OrderRepositoryInterface`. It also uses `EntityManager` to query entities directly, bypassing the repository abstraction entirely. The `EmailService` is also injected as a concrete class rather than an interface.

---

`src/Service/FakturowniaService.php` - high-level business logic depends on low-level details:

```php
// VIOLATION: Business service depends on concrete HTTP client and repository
final readonly class FakturowniaService implements FakturowniaServiceInterface
{
    public function __construct(
        private HttpClientInterface $httpClient,               // Low-level HTTP detail
        private LoggerInterface $logger,
        private string $apiToken,                              // Infrastructure config
        private string $baseUrl,                               // Infrastructure config
        private InvoiceValidationService $invoiceValidationService, // Concrete class!
        private PaymentRepository $paymentRepository,          // Concrete class!
        private int $departmentId,                             // Infrastructure config
        private int $departmentEurId,                          // Infrastructure config
        private int $warehouseId,                              // Infrastructure config
        private int $wzProductId                               // Infrastructure config
    ) {
    }
}
```

**Why it violates DIP:**
- `PaymentRepository` is a concrete class — should be `PaymentRepositoryInterface`
- `InvoiceValidationService` is a concrete class — should be an interface
- Infrastructure configuration values (`apiToken`, `baseUrl`, department IDs) are mixed with business dependencies. These should be encapsulated in a configuration value object or the low-level HTTP client abstraction.

## Rationale

1. **Decoupling**: High-level policy is isolated from low-level implementation details.

2. **Testability**: Interfaces can be easily mocked in tests without needing the real infrastructure.

3. **Flexibility**: Implementations can be swapped (e.g., switching from Fakturownia to another invoicing provider) without touching business logic.

4. **Architecture Boundaries**: DIP enforces clean layer separation — domain doesn't know about infrastructure.

5. **Framework Independence**: Business logic doesn't depend on Doctrine, Symfony, or any specific framework component.
