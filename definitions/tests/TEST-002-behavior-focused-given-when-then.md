# TEST-002: Behavior-Focused Tests with Given-When-Then

**Documentation:** https://github.com/team-mate-pro/tmp-standards/blob/main/definitions/tests/TEST-002-behavior-focused-given-when-then.md

## Check Method

| Method | Command |
|--------|---------|
| **AI** | `claude -p "$(cat vendor/team-mate-pro/tmp-standards/definitions/tests/TEST-002-behavior-focused-given-when-then.prompt.txt)" --cwd .` |

## Definition

Tests must focus on **observable behaviors** (inputs → outputs, state changes, side effects) rather than implementation details. Every test method must follow the **Given-When-Then** structure expressed through both the method name and inline comments. Tests should be organized into logical **comment blocks** that group related scenarios.

## Applies To

- All PHPUnit test classes (`*Test.php`)
- Unit, Integration, and Application tests

## Rules

### 1. Method Naming — Readable as a Sentence

Test methods must use the `given_when_then` pattern in camelCase and be **descriptive enough to read like a specification**. When PHPUnit runs with `--testdox`, the output should read like a book describing how the system works.

```
given{DetailedPrecondition}_when{SpecificAction}_then{ObservableOutcome}
```

The method name must answer three questions in plain language:
- **Given**: What is the starting state? (include the entity and its relevant condition)
- **When**: What action or event triggers the behavior?
- **Then**: What is the observable result from a business perspective?

**Good** — reads like documentation:
```
givenNewOrderWithAddressWithoutCoordinates_whenPersisted_thenGeocodesAndSetsCoordinates
givenNewOrderWithAddressThatAlreadyHasCoordinates_whenPersisted_thenSkipsGeocodingAndKeepsOriginal
givenPendingOrder_whenPaid_thenChangesStatusToPaidAndDispatchesPaymentReceivedEvent
givenExpiredPromoCode_whenAppliedToCart_thenThrowsPromoCodeExpiredException
```

**Bad** — too vague, no business context:
```
testGeocode                              // what scenario? what outcome?
givenOrder_whenPersisted_thenWorks       // "works" means nothing
givenAddress_whenCall_thenReturnsResult  // what address? what call? what result?
testCoordinatesAreSet                    // no GWT, no scenario context
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
 ✔ Given new order with address without coordinates when persisted then geocodes and sets coordinates
 ✔ Given new order with address that already has coordinates when persisted then skips geocoding and keeps original
 ✔ Given new order without delivery address when persisted then does nothing
 ✔ Given new order with address when geocoding returns null then nullifies coordinates and logs warning
 ✔ Given new order with address when geocoding returns zero zero then nullifies coordinates and logs warning
 ✔ Given new order with address that has zero coordinates when persisted then tries to geocode again
 ✔ Given existing order whose address has no coordinates when updated then geocodes and recomputes changeset
 ✔ Given existing order with valid coordinates when updated without address change then skips geocoding
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
    public function givenNewOrderWithAddressWithoutCoordinates_whenPersisted_thenGeocodesAndSetsCoordinates(): void
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
    public function givenNewOrderWithAddressThatAlreadyHasCoordinates_whenPersisted_thenSkipsGeocodingAndKeepsOriginal(): void
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
    public function givenNewOrderWithAddress_whenGeocodingReturnsNull_thenNullifiesCoordinatesAndLogsWarning(): void
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

### Vague, non-descriptive method names

```php
// WRONG: names are technically GWT but too vague to be useful
#[Test]
public function givenOrder_whenPersisted_thenWorks(): void { /* ... */ }
#[Test]
public function givenAddress_whenCall_thenReturnsResult(): void { /* ... */ }
#[Test]
public function givenData_whenProcessed_thenCorrect(): void { /* ... */ }
```

**Testdox output tells you nothing:**
```
 ✔ Given order when persisted then works
 ✔ Given address when call then returns result
 ✔ Given data when processed then correct
```

**Problems:**
- "works", "returns result", "correct" — these are meaningless assertions
- No specifics about _which_ order state, _what_ call, _what_ result
- A failing test gives zero context about what business rule broke

### Testing implementation instead of behavior

```php
// WRONG: testing internal implementation details
#[Test]
public function givenOrder_whenPersisted_thenCallsFindCoordinatesExactlyOnceWithCorrectArguments(): void
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
    public function givenNewOrder_whenCreated_thenHasPendingStatus(): void { /* ... */ }
    #[Test]
    public function givenPendingOrder_whenPaid_thenHasPaidStatus(): void { /* ... */ }
    #[Test]
    public function givenPaidOrder_whenShipped_thenHasShippedStatus(): void { /* ... */ }
    #[Test]
    public function givenNewOrder_whenCreatedWithInvalidEmail_thenThrowsException(): void { /* ... */ }
    #[Test]
    public function givenPendingOrder_whenCancelled_thenHasCancelledStatus(): void { /* ... */ }
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
    public function givenNewOrder_whenCreated_thenHasPendingStatus(): void { /* ... */ }
    #[Test]
    public function givenNewOrder_whenCreatedWithInvalidEmail_thenThrowsException(): void { /* ... */ }

    // ==========================================
    // Order payment
    // ==========================================

    #[Test]
    public function givenPendingOrder_whenPaid_thenHasPaidStatus(): void { /* ... */ }

    // ==========================================
    // Order shipping
    // ==========================================

    #[Test]
    public function givenPaidOrder_whenShipped_thenHasShippedStatus(): void { /* ... */ }

    // ==========================================
    // Order cancellation
    // ==========================================

    #[Test]
    public function givenPendingOrder_whenCancelled_thenHasCancelledStatus(): void { /* ... */ }
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
