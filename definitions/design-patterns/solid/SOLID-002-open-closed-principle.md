# SOLID-002: Open/Closed Principle (OCP)

**Documentation:** https://github.com/team-mate-pro/tmp-standards/blob/main/definitions/design-patterns/solid/SOLID-002-open-closed-principle.md

## Check Method

| Method | Command |
|--------|---------|
| **AI** | `claude -p "$(cat vendor/team-mate-pro/tmp-standards/definitions/design-patterns/solid/SOLID-002-open-closed-principle.prompt.txt)" --cwd .` |

## Definition

Software entities (classes, modules, functions) should be **open for extension but closed for modification**. When new behavior is needed, you should be able to add it without changing existing code. Avoid `switch`/`if-else` chains that dispatch on type or action — use polymorphism, strategy pattern, or handler registries instead.

## Applies To

- Command classes with action dispatching
- Service classes with type-based conditionals
- Importers / parsers with format-specific logic
- Any class using `switch` or `if-else` chains to select behavior

## Correct Usage

```php
// Strategy pattern - new actions added by registering new handlers
interface InvoiceActionHandlerInterface
{
    public function supports(string $action): bool;
    public function handle(SymfonyStyle $io, InputInterface $input): int;
}

final readonly class CreateInvoiceHandler implements InvoiceActionHandlerInterface
{
    public function supports(string $action): bool
    {
        return $action === 'create';
    }

    public function handle(SymfonyStyle $io, InputInterface $input): int
    {
        // create logic
    }
}

final readonly class MarkPaidHandler implements InvoiceActionHandlerInterface
{
    public function supports(string $action): bool
    {
        return $action === 'mark-paid';
    }

    public function handle(SymfonyStyle $io, InputInterface $input): int
    {
        // mark-paid logic
    }
}

// Command dispatches via registry - no modification needed to add new actions
final class FakturowniaInvoiceCommand extends Command
{
    /** @param iterable<InvoiceActionHandlerInterface> $handlers */
    public function __construct(
        private iterable $handlers,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $action = $input->getArgument('action');

        foreach ($this->handlers as $handler) {
            if ($handler->supports($action)) {
                return $handler->handle(new SymfonyStyle($input, $output), $input);
            }
        }

        throw new \InvalidArgumentException("Unknown action: $action");
    }
}
```

```php
// Map-based approach for value mapping - new types added via configuration
final readonly class RoofTypeMapper
{
    /** @var array<string, list<string>> */
    private const MAPPINGS = [
        'leftRight'  => ['dwuspadowy', 'prawo-lewo', 'lewo-prawo'],
        'front'      => ['jednospadowy przód'],
        'back'       => ['jednospadowy tył'],
    ];

    public function map(string $value): string
    {
        $value = mb_strtolower($value);

        foreach (self::MAPPINGS as $type => $keywords) {
            foreach ($keywords as $keyword) {
                if (str_contains($value, $keyword)) {
                    return $type;
                }
            }
        }

        return 'leftRight';
    }
}
```

## Violation

**Real example from `gate-backend`:**

`src/Command/FakturowniaInvoiceCommand.php` - switch statement to dispatch actions:

```php
// VIOLATION: Adding a new action requires modifying this method
switch ($action) {
    case 'create':
        return $this->createInvoice($io, $input);
    case 'mark-paid':
        return $this->markInvoiceAsPaid($io, $input);
    case 'status':
        return $this->checkInvoiceStatus($io, $input);
    case 'unlink-payment':
        return $this->unlinkBankingPayment($io, $input);
}
```

**Why it violates OCP:** To add a new action (e.g., `cancel`), you must modify this method. The class is not closed for modification.

---

`src/Command/SyncToFakturowniaCommand.php` - entity type switching:

```php
// VIOLATION: Adding a new entity type requires modifying this method
switch ($entity) {
    case 'customer':
        return $this->syncCustomers($io, $id, $all, $force);
    case 'order':
        return $this->syncOrders($io, $id, $all, $force);
}
```

**Why it violates OCP:** Supporting a new entity type (e.g., `invoice`) requires changing existing code.

---

`src/Modules/Offer/Infrastructure/Service/OfferFileImporterPl.php` - long if-else chains for type mapping:

```php
// VIOLATION: Adding a new roof type requires modifying this method
private function mapRoofType(string $value): string
{
    $value = mb_strtolower($value);

    if (str_contains($value, 'dwuspadowy') || str_contains($value, 'prawo-lewo') || str_contains($value, 'lewo-prawo')) {
        return 'leftRight';
    }
    if (str_contains($value, 'jednospadowy') && str_contains($value, 'przód')) {
        return 'front';
    }
    if (str_contains($value, 'jednospadowy') && str_contains($value, 'tył')) {
        return 'back';
    }

    return 'leftRight';
}
```

**Why it violates OCP:** Every new roof type or garage type requires modifying the method. A data-driven mapping (array, config, or enum) would allow extension without modification.

---

`src/Service/InvoiceAvailabilityService.php` - type-based conditionals for invoice types:

```php
// VIOLATION: Adding a new invoice type requires modifying this method
$result['order'] = $this->checkOrder($payment, $paymentType);

if ($isPrivateCustomer) {
    $result['receipt'] = $this->checkReceipt($payment, $paymentType);
}

if ($isBusinessCustomer) {
    $result['advance'] = $this->checkAdvance($payment, $paymentType, $paymentStatus, $isBusinessCustomer);
    $result['final'] = $this->checkFinal($payment, $order, $paymentType, $isBusinessCustomer);
    $result['vat'] = $this->checkVat($payment, $isBusinessCustomer);
}
```

**Why it violates OCP:** New invoice types require adding new conditionals. An invoice type checker registry would allow extension without modification.

## Rationale

1. **Safe Extension**: New behavior is added by creating new classes, not editing existing ones — reducing regression risk.

2. **Testability**: Existing tests remain valid when new handlers/strategies are added.

3. **Decoupling**: Each behavior variant is isolated in its own class, making it easier to understand and modify independently.

4. **Framework Alignment**: Symfony's tagged services (`#[AutoconfigureTag]`) natively support handler registries.
