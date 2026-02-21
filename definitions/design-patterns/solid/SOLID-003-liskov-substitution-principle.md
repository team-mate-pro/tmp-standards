# SOLID-003: Liskov Substitution Principle (LSP)

**Documentation:** https://github.com/team-mate-pro/tmp-standards/blob/main/definitions/design-patterns/solid/SOLID-003-liskov-substitution-principle.md

## Check Method

| Method | Command |
|--------|---------|
| **AI** | `claude -p "$(cat vendor/team-mate-pro/tmp-standards/definitions/design-patterns/solid/SOLID-003-liskov-substitution-principle.prompt.txt)" --cwd .` |

## Definition

Objects of a superclass should be **replaceable with objects of a subclass** without breaking correctness. If class `B` extends class `A`, then anywhere `A` is used, `B` must work correctly without surprising behavior. Subtypes must honor the contract (preconditions, postconditions, invariants) of their parent type.

## Applies To

- Classes extending abstract classes
- Classes implementing interfaces
- Method overrides
- Any subtype relationship

## Correct Usage

```php
// Interface defines a clear contract
interface InvoiceGeneratorInterface
{
    /**
     * @throws InvoiceGenerationException on failure
     */
    public function generate(Order $order): Invoice;
}

// Both implementations honor the same contract
final readonly class StandardInvoiceGenerator implements InvoiceGeneratorInterface
{
    public function generate(Order $order): Invoice
    {
        // Generate standard invoice - always returns Invoice or throws InvoiceGenerationException
        return new Invoice($order, InvoiceType::STANDARD);
    }
}

final readonly class ProformaInvoiceGenerator implements InvoiceGeneratorInterface
{
    public function generate(Order $order): Invoice
    {
        // Generate proforma - same contract, same exceptions
        return new Invoice($order, InvoiceType::PROFORMA);
    }
}

// Client code works with any implementation interchangeably
final readonly class InvoiceUseCase
{
    public function __construct(
        private InvoiceGeneratorInterface $generator,
    ) {
    }

    public function __invoke(Order $order): Invoice
    {
        return $this->generator->generate($order);
    }
}
```

## Violation

**Real example from `gate-backend`:**

`src/Repository/OrderRepository.php` - class implements 13 interfaces with mixed concerns:

```php
// VIOLATION: A repository implementing unrelated statistical interfaces
// breaks substitutability expectations
class OrderRepository extends ServiceEntityRepository implements
    OrderRepositoryInterface,
    RouteManagementOrderRepositoryInterface,
    OffersCountInterface,
    OrdersCountInterface,
    OrderIncomeSumInterface,
    OffersCountByMonthInterface,
    OrdersCountByMonthInterface,
    OffersCountByCountryInterface,
    OrdersCountByCountryInterface,
    OrderRevenueByMonthInterface,
    RevenueByCountryInterface,
    OffersByUserStatsInterface
{
    // ...
}
```

**Why it violates LSP:** A client that expects `OrderRepositoryInterface` gets an object that also has statistical query methods. If a different `OrderRepositoryInterface` implementation is substituted (e.g., a test double or a caching decorator), it would unexpectedly lack the statistical methods that other parts of the system depend on through the same concrete class. The repository cannot be cleanly substituted without also satisfying all 13 interface contracts.

---

**General violation pattern** - subclass weakening postconditions:

```php
interface FileExporterInterface
{
    /** @return string File path of the exported file */
    public function export(Order $order): string;
}

// VIOLATION: Subclass returns empty string instead of a valid path
final class NullExporter implements FileExporterInterface
{
    public function export(Order $order): string
    {
        return ''; // Breaks the postcondition - callers expect a valid file path
    }
}
```

**General violation pattern** - subclass throwing unexpected exceptions:

```php
abstract class AbstractRepository
{
    /** @throws NotFoundException */
    abstract public function findOrFail(int $id): object;
}

// VIOLATION: Subclass throws a different exception type
class CachedRepository extends AbstractRepository
{
    public function findOrFail(int $id): object
    {
        throw new \RuntimeException('Cache unavailable'); // Unexpected exception type
    }
}
```

## Rationale

1. **Predictability**: Client code should work with any implementation of an interface without surprises.

2. **Safe Polymorphism**: When substituting implementations (e.g., for testing or decoration), the system should continue to work correctly.

3. **Design by Contract**: Subtypes must respect preconditions, postconditions, and invariants defined by the parent type.

4. **Dependency Injection**: LSP is the foundation of DI — swapping implementations must not break consumers.
