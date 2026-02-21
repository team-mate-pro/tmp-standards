# TEST-002: Behavior-Focused Tests with Given-When-Then

**Documentation:** https://github.com/team-mate-pro/tmp-standards/blob/main/definitions/tests/TEST-002-behavior-focused-given-when-then.md

## Check Method

| Method | Command |
|--------|---------|
| **AI** | `claude -p "$(cat vendor/team-mate-pro/tmp-standards/definitions/tests/TEST-002-behavior-focused-given-when-then.prompt.txt)" --cwd .` |

## Definition

Tests must focus on **observable behaviors** (inputs → outputs, state changes, side effects) rather than implementation details. Every test method must have a **descriptive camelCase name** (no underscores, no spaces) and use the **Given-When-Then** structure in **inline comments only** (not in the method name). Tests should be organized into logical **comment blocks** that group related scenarios.

## Applies To

- All PHPUnit test classes (`*Test.php`)
- Unit, Integration, and Application tests

## Rules

### 1. Method Naming — Readable as a Sentence

Test method names must be **pure camelCase without underscores or spaces**. They must be descriptive enough that PHPUnit `--testdox` output reads like a book describing how the system works.

The `given`, `when`, `then` keywords are **NOT allowed** in method names — they belong only in inline comments (see Rule 2). The method name itself should be a natural, descriptive sentence in camelCase.

**Good** — reads like documentation:
```
newOrderWithAddressGetsGeocodedCoordinatesOnPersist
orderWithExistingCoordinatesKeepsOriginalOnPersist
orderWithoutDeliveryAddressIsIgnoredOnPersist
geocodingFailureNullifiesCoordinatesAndLogsWarning
zeroCoordinatesTriggerReGeocodingOnPersist
updatedOrderWithoutCoordinatesGetsGeocodedAndChangesetRecomputed
paidOrderChangesStatusAndDispatchesPaymentReceivedEvent
expiredPromoCodeThrowsExceptionWhenAppliedToCart
```

**Bad** — underscores, GWT keywords in name, or too vague:
```
givenNewOrder_whenPersisted_thenWorks         // underscores + GWT keywords in name
given_order_when_persisted_then_works         // snake_case
testGeocode                                   // says nothing about scenario
testCoordinatesAreSet                         // no context, "test" prefix
coordinatesWork                               // "work" is meaningless
processOrder                                  // what about the order? what outcome?
```

The goal: a new developer running `composer tests:unit` should understand the system's business rules **just by reading the test output**, without opening a single test file.

### 2. Inline Comments

Each test must have three clearly separated sections with comments:

```php
// Given: <describe the initial state/preconditions>
// When: <describe the action being performed>
// Then: <describe the expected observable outcome>
```

### 3. Comment Block Sections

Related test methods must be grouped using comment separators:

```php
// ==========================================
// <Section description>
// ==========================================
```

### 4. Test What, Not How

- **DO** test observable behavior: return values, state changes, dispatched events, logged messages, thrown exceptions
- **DO NOT** test internal implementation: private method calls, internal variable values, execution order of internal steps

## Expected Testdox Output

When tests are run with `--testdox`, the output should read like a behavioral specification of the system. A developer who has never seen the codebase should understand the business rules:

```
Address Coordinates Listener (App\Tests\Unit\EventListener\AddressCoordinatesListener)
 ✔ New order with address gets geocoded coordinates on persist
 ✔ Order with existing coordinates keeps original on persist
 ✔ Order without delivery address is ignored on persist
 ✔ Geocoding failure nullifies coordinates and logs warning
 ✔ Geocoding returning zero zero nullifies coordinates and logs warning
 ✔ Zero coordinates trigger re geocoding on persist
 ✔ Updated order without coordinates gets geocoded and changeset recomputed
 ✔ Updated order with valid coordinates skips geocoding when address unchanged
```

Reading this output top-to-bottom tells you:
- What the listener does on new orders (geocodes addresses)
- When it skips geocoding (coordinates already exist)
- How it handles edge cases (no address, null response, zero coordinates)
- How it behaves on updates vs creates

## Correct Usage

```php
#[CoversClass(AddressCoordinatesListener::class)]
final class AddressCoordinatesListenerTest extends TestCase
{
    private CoordinatesFinderInterface&MockObject $coordinatesFinder;
    private LoggerInterface&MockObject $logger;
    private AddressCoordinatesListener $listener;

    protected function setUp(): void
    {
        $this->coordinatesFinder = $this->createMock(CoordinatesFinderInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->listener = new AddressCoordinatesListener($this->coordinatesFinder, $this->logger);
    }

    // ==========================================
    // prePersist — new order creation
    // ==========================================

    #[Test]
    public function newOrderWithAddressGetsGeocodedCoordinatesOnPersist(): void
    {
        // Given: a new order with a delivery address but no coordinates
        $address = new Address(street: 'Test 1', city: 'Warszawa', zipCode: '00-001', country: 'PL');
        $order = $this->createOrderWithAddress($address);

        // When: the order is persisted, and Google returns valid coordinates
        $this->stubFinderReturns(52.2297, 21.0122);
        $this->listener->prePersist($order, $this->createPrePersistEvent());

        // Then: the delivery address should have the geocoded coordinates
        $coords = $order->getDeliveryAddress()?->getCoordinates();
        $this->assertNotNull($coords);
        $this->assertSame(52.2297, $coords->getLatitude());
        $this->assertSame(21.0122, $coords->getLongitude());
    }

    #[Test]
    public function orderWithExistingCoordinatesKeepsOriginalOnPersist(): void
    {
        // Given: a new order with a delivery address that already has coordinates
        $address = new Address(
            street: 'Test 1',
            city: 'Warszawa',
            zipCode: '00-001',
            country: 'PL',
            coordinates: new Coordinates(50.0, 19.0),
        );
        $order = $this->createOrderWithAddress($address);

        // When: the order is persisted
        $this->coordinatesFinder->expects($this->never())->method('findCoordinates');
        $this->listener->prePersist($order, $this->createPrePersistEvent());

        // Then: the original coordinates should be preserved (no API call made)
        $coords = $order->getDeliveryAddress()?->getCoordinates();
        $this->assertNotNull($coords);
        $this->assertSame(50.0, $coords->getLatitude());
        $this->assertSame(19.0, $coords->getLongitude());
    }

    // ==========================================
    // prePersist — geocoding failure scenarios
    // ==========================================

    #[Test]
    public function geocodingFailureNullifiesCoordinatesAndLogsWarning(): void
    {
        // Given: a new order with a delivery address but no coordinates
        $address = new Address(street: 'Test 1', city: 'Warszawa', zipCode: '00-001', country: 'PL');
        $order = $this->createOrderWithAddress($address);

        // When: the order is persisted, but the API returns null
        $this->coordinatesFinder->method('findCoordinates')->willReturn(null);
        $this->logger->expects($this->once())
            ->method('warning')
            ->with('AddressCoordinatesListener: could not geocode delivery address', $this->anything());
        $this->listener->prePersist($order, $this->createPrePersistEvent());

        // Then: coordinates are set to null and a warning is logged
        $this->assertNull($order->getDeliveryAddress()?->getCoordinates());
    }

    // ==========================================
    // Helpers
    // ==========================================

    private function createOrderWithAddress(?Address $address): Order
    {
        $order = new Order();
        $order->setDeliveryAddress($address);

        return $order;
    }
}
```

## Violation

### Missing Given-When-Then structure

```php
// WRONG: no GWT naming, no inline comments, tests implementation details
#[Test]
public function testGeocode(): void
{
    $address = new Address(street: 'Test 1', city: 'Warszawa', zipCode: '00-001', country: 'PL');
    $order = new Order();
    $order->setDeliveryAddress($address);

    $coords = $this->createMock(CoordinatesInterface::class);
    $coords->method('getLatitude')->willReturn(52.2297);
    $coords->method('getLongitude')->willReturn(21.0122);
    $this->coordinatesFinder->method('findCoordinates')->willReturn($coords);

    $this->listener->prePersist($order, $this->createPrePersistEvent());

    $this->assertSame(52.2297, $order->getDeliveryAddress()->getCoordinates()->getLatitude());
}
```

**Problems:**
- Method name `testGeocode` says nothing about the scenario or expected behavior
- No `// Given:`, `// When:`, `// Then:` comments — hard to understand what is being tested
- No comment block sections — tests are an unorganized flat list

**Testdox output is useless:**
```
Address Coordinates Listener
 ✔ Geocode
```
Compare with the correct version — no business knowledge conveyed at all.

### Underscores and GWT keywords in method name

```php
// WRONG: underscores act as spaces, given/when/then belong in comments only
#[Test]
public function givenNewOrder_whenPersisted_thenGeocodesAddress(): void { /* ... */ }
#[Test]
public function given_order_when_paid_then_status_changes(): void { /* ... */ }
```

**Problems:**
- Underscores are not allowed in method names
- `given`, `when`, `then` keywords pollute the method name — they belong in inline comments only

**Correct:**
```php
#[Test]
public function newOrderGetsGeocodedAddressOnPersist(): void { /* ... */ }
#[Test]
public function paidOrderChangesStatusToPaid(): void { /* ... */ }
```

### Vague, non-descriptive method names

```php
// WRONG: names are too vague to be useful
#[Test]
public function orderWorks(): void { /* ... */ }
#[Test]
public function addressReturnsResult(): void { /* ... */ }
#[Test]
public function dataIsProcessedCorrectly(): void { /* ... */ }
```

**Testdox output tells you nothing:**
```
 ✔ Order works
 ✔ Address returns result
 ✔ Data is processed correctly
```

**Problems:**
- "works", "returns result", "correctly" — these are meaningless assertions
- No specifics about _which_ order state, _what_ address scenario, _what_ result
- A failing test gives zero context about what business rule broke

### Testing implementation instead of behavior

```php
// WRONG: testing internal implementation details
#[Test]
public function persistedOrderCallsFindCoordinatesExactlyOnceWithCorrectArguments(): void
{
    $address = new Address(street: 'Test 1', city: 'Warszawa', zipCode: '00-001', country: 'PL');
    $order = $this->createOrderWithAddress($address);

    // Testing HOW it works internally, not WHAT it produces
    $this->coordinatesFinder->expects($this->once())
        ->method('findCoordinates')
        ->with($this->callback(function (string $arg) {
            return str_contains($arg, 'Test 1') && str_contains($arg, 'Warszawa');
        }));

    $this->listener->prePersist($order, $this->createPrePersistEvent());

    // No assertion on observable outcome (coordinates on the entity)
}
```

**Problems:**
- Focuses on verifying *how* the internal collaborator is called (argument matching, call count)
- Does not assert the observable outcome (coordinates set on the entity)
- Test will break if internal implementation changes even if behavior stays the same

### Missing comment block sections

```php
final class OrderServiceTest extends TestCase
{
    // WRONG: all tests dumped in a flat list without grouping
    #[Test]
    public function newOrderHasPendingStatus(): void { /* ... */ }
    #[Test]
    public function paidOrderChangesStatusToPaid(): void { /* ... */ }
    #[Test]
    public function shippedOrderChangesStatusToShipped(): void { /* ... */ }
    #[Test]
    public function orderWithInvalidEmailThrowsException(): void { /* ... */ }
    #[Test]
    public function cancelledPendingOrderChangesStatusToCancelled(): void { /* ... */ }
}
```

**Correct:** Group into logical sections:

```php
final class OrderServiceTest extends TestCase
{
    // ==========================================
    // Order creation
    // ==========================================

    #[Test]
    public function newOrderHasPendingStatus(): void { /* ... */ }
    #[Test]
    public function orderWithInvalidEmailThrowsException(): void { /* ... */ }

    // ==========================================
    // Order payment
    // ==========================================

    #[Test]
    public function paidOrderChangesStatusToPaid(): void { /* ... */ }

    // ==========================================
    // Order shipping
    // ==========================================

    #[Test]
    public function shippedOrderChangesStatusToShipped(): void { /* ... */ }

    // ==========================================
    // Order cancellation
    // ==========================================

    #[Test]
    public function cancelledPendingOrderChangesStatusToCancelled(): void { /* ... */ }
}
```

## Rationale

1. **Readability**: Given-When-Then method names serve as living documentation — any developer can understand the scenario without reading the test body.

2. **Business Focus**: Testing observable behaviors ensures tests validate what the system *does* for the user, not how it internally achieves it. This directly maps to business requirements.

3. **Refactoring Safety**: Tests that assert on outputs and state changes do not break when internal implementation is refactored. This makes refactoring safe and fast.

4. **Discoverability**: Comment block sections act as a table of contents. When a test fails, the section header immediately tells you which functional area is broken.

5. **Maintainability**: Inline `// Given:`, `// When:`, `// Then:` comments clearly separate arrangement, action, and assertion — making tests easy to modify and extend.

## Reference Implementation

- [`AddressCoordinatesListenerTest.php`](https://github.com/team-mate-pro/gate-backend/blob/main/tests/Unit/EventListener/AddressCoordinatesListenerTest.php) — exemplary test class following all rules of this standard.
