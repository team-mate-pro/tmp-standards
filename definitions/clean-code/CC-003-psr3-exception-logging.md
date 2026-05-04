# CC-003: PSR-3 Exception Logging Context

**Documentation:** https://github.com/team-mate-pro/tmp-standards/blob/main/definitions/clean-code/CC-003-psr3-exception-logging.md

## Check Method

| Method | Command |
|--------|---------|
| **PHPSTAN** | `composer phpstan` — rule `logger.exceptionContextKey` enforces CC-003.1 (Throwable must be under `'exception'` key) |
| **AI** | `claude -p "$(cat vendor/team-mate-pro/tmp-standards/definitions/clean-code/CC-003-psr3-exception-logging.prompt.txt)" --cwd .` — full standard (CC-003.1 – CC-003.6) |

## Definition

When an exception is logged, the `\Throwable` object **must** be passed in the PSR-3 logger context under the reserved `'exception'` key. This is a hard requirement of [PSR-3 §1.3](https://www.php-fig.org/psr/psr-3/#13-context):

> If an Exception object is passed in the context data, it MUST be in the `'exception'` key. Logging exceptions is a common pattern and this allows implementors to extract a stack trace from the exception when the log backend supports it.

Honoring this key lets Monolog processors, Sentry, Datadog, New Relic, and ELK stacks automatically extract the full stack trace, previous-exception chain, exception class, file, and line — producing an actionable error report instead of a one-line string.

This standard is **about how to log an exception**, not about whether to catch one. Silently swallowing exceptions is still governed by [CC-002: Fail Fast](CC-002-fail-fast.md). See the Rationale for the interaction between the two rules.

## Applies To

- Every call to a PSR-3 `LoggerInterface` method (`error`, `critical`, `warning`, `notice`, `info`, `debug`, `log`)
- `catch` blocks that log the caught throwable
- Error/exception handlers and message-bus failure handlers
- Cron, consumer, and worker entry points that report failures per item

## Rules

### CC-003.1: Pass the Throwable Under the `exception` Key

When the code has access to a `\Throwable`, it **must** be passed as `$logger->...($message, ['exception' => $e])`. Any other key (`'error'`, `'e'`, `'throwable'`, `'ex'`) defeats the PSR-3 contract and suppresses stack-trace extraction in downstream tooling.

### CC-003.2: Do Not Inline Exception Data Into the Message

The log message itself must describe *what the application was trying to do* — not the exception internals. Do not concatenate `$e->getMessage()`, `$e->getTraceAsString()`, `$e->getFile()`, `$e->getLine()`, `(string) $e`, or `$e->__toString()` into the message string. That data belongs in the `exception` context, where the log backend formats it structurally.

### CC-003.3: Do Not Hand-Serialize the Exception Into Context

Do not flatten a throwable into ad-hoc context keys such as `['trace' => $e->getTraceAsString(), 'message' => $e->getMessage(), 'previous' => $e->getPrevious()]`. Pass the throwable object itself under `'exception'`; the logger (and processors like Monolog's `PsrLogMessageProcessor` or Sentry's handler) will extract a faithful, recursive representation that a string dump cannot match.

### CC-003.4: Choose the Right Severity

Logging an exception at `info` or `debug` hides real failures. Unexpected throwables that the caller could not handle must be logged at `error` or `critical`. Expected, recoverable throwables (e.g., a retryable transport failure that will be retried) may be logged at `warning`, but the `exception` key rule still applies.

### CC-003.5: Only Log-and-Swallow at Explicit Boundaries

A `catch (\Throwable $e) { $logger->error(...); }` that does not rethrow is a form of silent failure and conflicts with [CC-002.2: No Silent Failures](CC-002-fail-fast.md#cc-0022-no-silent-failures) unless the catch sits at an explicit boundary where continuing is a deliberate decision:

- Message/queue consumers, cron entry points, CLI commands that must continue with the next item
- Best-effort side effects whose failure must not break the parent transaction (analytics, cache warm-up, notification dispatch)
- Top-level HTTP/kernel exception listeners that translate throwables into responses
- `finally` cleanup where rethrowing would mask the original error

Inside UseCases, domain services, repositories, and factories, exceptions must propagate — log them only if you rethrow (or log at the boundary that catches them).

### CC-003.6: Do Not Log the Same Exception Twice

If a lower layer already logged the throwable under `exception`, re-logging it in a caller produces duplicate Sentry events and noisy dashboards. Either log at the boundary **or** at the failing operation — not both. Prefer the boundary.

## Correct Usage

### Minimal PSR-3 Call

```php
try {
    $this->paymentGateway->charge($order, $amount);
} catch (PaymentGatewayException $e) {
    $this->logger->error('Payment charge failed', [
        'exception' => $e,
        'orderId'   => $order->getId(),
        'amount'    => $amount,
    ]);

    throw $e; // propagate — see CC-002 / CC-003.5
}
```

The message describes the operation. The throwable goes under `exception`. Domain identifiers are siblings in the context array so they become searchable fields in the log backend.

### Consumer Boundary — Log-and-Continue Is Deliberate

```php
final readonly class ImportCustomerMessageHandler
{
    public function __construct(
        private ImportCustomerUseCase $useCase,
        private LoggerInterface $logger,
    ) {}

    public function __invoke(ImportCustomerMessage $message): void
    {
        try {
            ($this->useCase)($message->toDto());
        } catch (\Throwable $e) {
            // Boundary: one failed message must not stop the consumer.
            $this->logger->error('Customer import failed', [
                'exception'  => $e,
                'customerId' => $message->getCustomerId(),
            ]);

            throw $e; // let the bus decide retry / DLQ based on the exception
        }
    }
}
```

### Best-Effort Side Effect

```php
public function onOrderPlaced(OrderPlacedEvent $event): void
{
    try {
        $this->analytics->track('order_placed', $event->toPayload());
    } catch (\Throwable $e) {
        // Analytics failure must never break order placement.
        $this->logger->warning('Analytics tracking failed', [
            'exception' => $e,
            'orderId'   => $event->getOrderId(),
        ]);
    }
}
```

### Top-Level HTTP Listener

```php
final readonly class ExceptionLoggingListener
{
    public function __construct(private LoggerInterface $logger) {}

    public function __invoke(ExceptionEvent $event): void
    {
        $throwable = $event->getThrowable();

        $this->logger->error('Unhandled exception reached the HTTP kernel', [
            'exception' => $throwable,
            'uri'       => $event->getRequest()->getUri(),
            'method'    => $event->getRequest()->getMethod(),
        ]);
    }
}
```

## Violation

### Exception String Concatenated Into the Message

```php
// WRONG — PSR-3 violation; Sentry/Monolog receive no throwable object
try {
    $this->paymentGateway->charge($order, $amount);
} catch (PaymentGatewayException $e) {
    $this->logger->error('Payment failed: ' . $e->getMessage()); // ❌
    $this->logger->error('Payment failed: ' . $e);               // ❌
    $this->logger->error("Payment failed: {$e->getMessage()} at {$e->getFile()}:{$e->getLine()}"); // ❌
    throw $e;
}
```

**Problems:**
- No stack trace reaches the log backend.
- Previous-exception chain is lost.
- Grouping/fingerprinting in Sentry breaks — each unique message becomes a new issue.
- Structured searches (`exception.class:PaymentGatewayException`) stop working.

### Wrong Context Key

```php
// WRONG — the key MUST be 'exception' per PSR-3 §1.3
$this->logger->error('Payment failed', [
    'error'     => $e, // ❌ not extracted as a throwable
    'throwable' => $e, // ❌
    'ex'        => $e, // ❌
]);
```

### Hand-Serialized Throwable

```php
// WRONG — rebuilds, badly, what the logger already does correctly
$this->logger->error('Payment failed', [
    'message'  => $e->getMessage(),
    'trace'    => $e->getTraceAsString(),
    'file'     => $e->getFile(),
    'line'     => $e->getLine(),
    'previous' => $e->getPrevious()?->getMessage(),
]);
```

**Problems:**
- The previous-exception chain is truncated to a single message.
- Arguments in the stack frames are dropped.
- Sentry will not recognize this as an exception event — it becomes a plain log line.

### Silent Swallow Disguised as a Log Call (CC-003.5 + CC-002)

```php
// WRONG — this is not "handling", it is hiding a bug
public function assignPlayerToTeam(string $playerId, string $teamId): void
{
    try {
        $player = $this->playerRepository->get($playerId);
        $team   = $this->teamRepository->get($teamId);
        $player->assignTo($team);
        $this->playerRepository->save($player);
    } catch (\Throwable $e) {
        $this->logger->error('Something went wrong', ['exception' => $e]); // ❌ caller thinks it succeeded
    }
}
```

**Problems:**
- The caller receives `void` and assumes success.
- Fail-fast is defeated: an invalid state (no assignment happened) silently propagates.
- The log entry is real, but no alert connects back to user-visible impact.

**Correct:** remove the catch and let the exception propagate, or catch *only* the specific throwable type you can meaningfully handle and rethrow a domain exception:

```php
public function assignPlayerToTeam(string $playerId, string $teamId): void
{
    $player = $this->playerRepository->get($playerId);
    $team   = $this->teamRepository->get($teamId);
    $player->assignTo($team);
    $this->playerRepository->save($player);
}
```

### Double Logging

```php
// WRONG — the UseCase logs and rethrows, the controller catches and logs again
// Sentry ends up with two events for a single failure.

// UseCase
try {
    ($this->paymentUseCase)($dto);
} catch (\Throwable $e) {
    $this->logger->error('Payment use case failed', ['exception' => $e]); // ❌
    throw $e;
}

// Controller
try {
    ($this->paymentUseCase)($dto);
} catch (\Throwable $e) {
    $this->logger->error('Payment controller failed', ['exception' => $e]); // ❌ duplicate
    return $this->response(500);
}
```

**Correct:** log once, at the outermost boundary that has the full context (request id, user id, route). Lower layers just throw.

### Wrong Severity

```php
// WRONG — an unexpected DB failure hidden at info level
try {
    $this->em->flush();
} catch (\Throwable $e) {
    $this->logger->info('Flush issue', ['exception' => $e]); // ❌ will never alert
}
```

Unexpected throwables belong at `error` or `critical` so alerting rules fire.

## Rationale

1. **Tooling contract.** PSR-3 is the contract every serious PHP logger, Sentry integration, and APM implements. The `exception` key is the only place they look for a throwable. Use any other key and the downstream pipeline degrades to plain text.

2. **Full stack trace, for free.** A hand-built `getTraceAsString()` loses frame arguments, previous-exception chain depth, and any attached data. The logger/processor extracts all of it — but only if it sees a `\Throwable` under `exception`.

3. **Stable grouping.** Sentry groups events by exception class + trace fingerprint. When the message carries the exception text, every unique value (order id, user id, timestamp) creates a new Sentry issue, and the real signal is buried under noise.

4. **Message vs. data separation.** A PSR-3 message should describe the operation, not the failure details. Keeping identifiers as context fields (`orderId`, `customerId`, `uri`) makes logs searchable and aggregatable; baking them into the message string makes them invisible to the log backend.

5. **Interaction with CC-002 (Fail Fast).** Fail-fast says: do not let invalid state continue silently. This standard says: when you *do* log an exception, log it correctly. The two rules are complementary, not contradictory, as long as CC-003.5 is respected:

   - **Inside** UseCases, services, factories, repositories, entities → do not catch-and-log-and-swallow. Let the exception propagate. This is pure fail-fast.
   - **At** a boundary (message handler, cron, best-effort side effect, top-level listener) → catching is a deliberate decision about *process continuity*, not about hiding bugs. Log under `exception` and either rethrow for the framework (bus retry / HTTP response) or, where swallowing is truly the business decision (analytics), accept it and log at the right level.

   The dangerous pattern is catching `\Throwable` mid-stack "so nothing breaks" and logging it away. That is not fail-fast, and CC-003 does not rescue it — CC-003.5 explicitly forbids it.

6. **One failure, one event.** Logging the same throwable at every layer it passes through produces duplicate alerts and wastes observability budget. Pick the boundary where full request context is available (the HTTP exception listener, the message-handler wrapper) and log there.

## Reference

- [PSR-3: Logger Interface (§1.3 Context)](https://www.php-fig.org/psr/psr-3/#13-context)
- [Monolog — Exception handling](https://github.com/Seldaek/monolog/blob/main/doc/01-usage.md)
- [Sentry for PHP — Capturing exceptions via Monolog handler](https://docs.sentry.io/platforms/php/guides/monolog/)
- [CC-002: Fail Fast](CC-002-fail-fast.md)
