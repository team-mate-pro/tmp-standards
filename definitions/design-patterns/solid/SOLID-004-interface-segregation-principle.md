# SOLID-004: Interface Segregation Principle (ISP)

**Documentation:** https://github.com/team-mate-pro/tmp-standards/blob/main/definitions/design-patterns/solid/SOLID-004-interface-segregation-principle.md

## Check Method

| Method | Command |
|--------|---------|
| **AI** | `claude -p "$(cat vendor/team-mate-pro/tmp-standards/definitions/design-patterns/solid/SOLID-004-interface-segregation-principle.prompt.txt)" --cwd .` |

## Definition

Clients should **not be forced to depend on interfaces they do not use**. Instead of one large "fat" interface, split it into smaller, role-specific interfaces. A class should only need to implement the methods that are relevant to its actual responsibility.

## Applies To

- Interface definitions
- Classes implementing multiple interfaces
- Repository interfaces
- Service contracts

## Correct Usage

```php
// Small, focused interfaces - each serves one role
interface OrderRepositoryInterface
{
    public function find(int $id): ?Order;
    public function save(Order $order): void;
    public function remove(Order $order): void;
}

interface OrderStatsQueryInterface
{
    public function countOrders(TimeRange $range): int;
    public function sumRevenue(TimeRange $range): Money;
}

interface OfferStatsQueryInterface
{
    public function countOffers(TimeRange $range): int;
    public function countOffersByCountry(TimeRange $range): array;
}

// Repository implements only what it needs
class OrderRepository extends ServiceEntityRepository implements OrderRepositoryInterface
{
    // Only CRUD methods - no stats
}

// Stats are handled by a dedicated read model
class OrderStatsQuery implements OrderStatsQueryInterface
{
    // Only stats methods - no CRUD
}
```

```php
// Consumers depend only on the interface they actually use
final readonly class CreateOrderUseCase
{
    public function __construct(
        private OrderRepositoryInterface $repository, // Only needs CRUD
    ) {
    }
}

final readonly class DashboardStatsService
{
    public function __construct(
        private OrderStatsQueryInterface $orderStats, // Only needs stats
        private OfferStatsQueryInterface $offerStats,
    ) {
    }
}
```

## Violation

**Real example from `gate-backend`:**

`src/Repository/OrderRepository.php` - implements 13 interfaces forcing all methods into one class:

```php
// VIOLATION: Fat repository implementing interfaces for CRUD + routing + 10 different stat queries
class OrderRepository extends ServiceEntityRepository implements
    OrderRepositoryInterface,                      // CRUD
    RouteManagementOrderRepositoryInterface,        // Route management
    OffersCountInterface,                           // Stats: offer count
    OrdersCountInterface,                           // Stats: order count
    OrderIncomeSumInterface,                        // Stats: income sum
    OffersCountByMonthInterface,                    // Stats: offers by month
    OrdersCountByMonthInterface,                    // Stats: orders by month
    OffersCountByCountryInterface,                  // Stats: offers by country
    OrdersCountByCountryInterface,                  // Stats: orders by country
    OrderRevenueByMonthInterface,                   // Stats: revenue by month
    RevenueByCountryInterface,                      // Stats: revenue by country
    OffersByUserStatsInterface                      // Stats: offers by user
{
    // All methods forced into a single class
}
```

**Why it violates ISP:** A consumer that only needs `OrderRepositoryInterface` (basic CRUD) is forced to depend on a class that also knows about revenue calculations, country statistics, and route management. Each statistical interface is a separate concern that should be served by a dedicated query class.

**Impact:**
- `CreateOrderUseCase` depends on a class that has 13 interfaces worth of methods
- Changes to any stats query method could affect the repository class used for CRUD
- Testing requires mocking a massive interface surface

---

**General violation pattern** - fat interface forcing empty implementations:

```php
// VIOLATION: Fat interface forces all importers to implement every method
interface FileImporterInterface
{
    public function importFromCsv(string $path): array;
    public function importFromXlsx(string $path): array;
    public function importFromPdf(string $path): array;
    public function importFromApi(string $endpoint): array;
}

// Slovak importer doesn't support PDF but must implement the method
class OfferFileImporterSk implements FileImporterInterface
{
    public function importFromPdf(string $path): array
    {
        throw new \BadMethodCallException('PDF import not supported for SK.');
    }
}
```

## Rationale

1. **Minimal Dependencies**: Classes only depend on the methods they actually use, reducing coupling.

2. **Easier Testing**: Mocking a 2-method interface is simpler than mocking a 20-method interface.

3. **Independent Deployment**: Changes to one interface don't force recompilation/retesting of unrelated consumers.

4. **Clear Contracts**: Small interfaces clearly communicate the role a consumer expects.

5. **CQRS Alignment**: Separating read (stats queries) from write (repository CRUD) interfaces naturally follows CQRS principles.
