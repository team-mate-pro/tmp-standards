# SOLID-001: Single Responsibility Principle (SRP)

**Documentation:** https://github.com/team-mate-pro/tmp-standards/blob/main/definitions/design-patterns/solid/SOLID-001-single-responsibility-principle.md

## Check Method

| Method | Command |
|--------|---------|
| **AI** | `claude -p "$(cat vendor/team-mate-pro/tmp-standards/definitions/design-patterns/solid/SOLID-001-single-responsibility-principle.prompt.txt)" --cwd .` |

## Definition

A class should have **only one reason to change**. Each class must encapsulate a single responsibility or concern. When a class handles multiple unrelated concerns (e.g., persistence, email notifications, template selection, and business logic), it becomes a "god class" that is hard to maintain, test, and extend.

## Applies To

- Entity classes
- Service classes
- Controller classes
- Any class in `src/`

## Correct Usage

```php
// Each service handles ONE concern

final readonly class MessageNotificationService
{
    public function __construct(
        private EmailSenderInterface $emailSender,
        private LoggerInterface $logger,
    ) {
    }

    public function notifyNewMessage(Order $order, string $recipientEmail): void
    {
        $this->emailSender->send(
            to: $recipientEmail,
            subject: 'New message for order #' . $order->getNumber(),
            template: 'emails/message/new_message.html.twig',
            context: ['order' => $order],
        );
    }
}

final readonly class InvoiceNotificationService
{
    public function __construct(
        private EmailSenderInterface $emailSender,
    ) {
    }

    public function notifyInvoiceReady(Order $order, string $recipientEmail): void
    {
        $this->emailSender->send(
            to: $recipientEmail,
            subject: 'Invoice for order #' . $order->getNumber(),
            template: 'emails/invoice/invoice_notification.html.twig',
            context: ['order' => $order],
        );
    }
}
```

```php
// Entity handles ONLY its own data and domain invariants
final class Order
{
    // Only order-specific fields and domain logic
    private OrderNumber $number;
    private OrderStatusCode $status;
    private Collection $products;

    public function accept(): void
    {
        if ($this->status !== OrderStatusCode::PENDING) {
            throw new \DomainException('Only pending orders can be accepted.');
        }
        $this->status = OrderStatusCode::ACCEPTED;
    }
}
```

## Violation

**Real example from `gate-backend`:**

`src/Service/EmailService.php` (431 lines) - handles multiple unrelated notification concerns in one class:

```php
// VIOLATION: One class handles messages, attachments, AND invoice notifications
final class EmailService
{
    // Message templates (seller + customer)
    private const TEMPLATE_NEW_MESSAGE_SELLER = [
        'pl' => 'emails/message/new_message_notification_seller.html.twig',
        'sk' => 'emails/message/new_message_notification_seller.html.twig',
    ];
    private const TEMPLATE_NEW_MESSAGE_CUSTOMER = [
        'pl' => 'emails/message/new_message_notification_customer.html.twig',
        'sk' => 'emails/sk/message/new_message_notification_customer.html.twig',
    ];

    // Attachment templates (seller + customer) - different concern!
    private const TEMPLATE_NEW_ATTACHMENT_SELLER = [
        'pl' => 'emails/order/new_attachment_notification_seller.html.twig',
    ];
    private const TEMPLATE_NEW_ATTACHMENT_CUSTOMER = [
        'pl' => 'emails/order/new_attachment_notification_customer.html.twig',
    ];

    // Invoice templates - yet another concern!
    private const TEMPLATE_INVOICE_NOTIFICATION = [
        'pl' => 'emails/invoice/invoice_notification.html.twig',
    ];

    // Methods for ALL notification types in one class
    public function sendNewMessageNotificationToSeller(...): void { /* ... */ }
    public function sendNewMessageNotificationToCustomer(...): void { /* ... */ }
    public function sendNewAttachmentNotificationToSeller(...): void { /* ... */ }
    public function sendNewAttachmentNotificationToCustomer(...): void { /* ... */ }
    public function sendInvoiceNotification(...): void { /* ... */ }
}
```

**Why it violates SRP:** This class has at least 3 reasons to change: message notification logic, attachment notification logic, and invoice notification logic. Each concern should be a separate service.

---

`src/Service/FakturowniaWebhookService.php` - one class actively performs webhook parsing, payment status management, AND order completion logic:

```php
// VIOLATION: Three unrelated active responsibilities in one service
final class FakturowniaWebhookService
{
    public function __construct(
        private readonly OrderRepository $orderRepository,
        private readonly PaymentRepository $paymentRepository,
        private readonly OrderStatusRepository $orderStatusRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly LoggerInterface $logger
    ) {
    }

    // RESPONSIBILITY #1: Webhook event routing
    public function handleInvoicePaid(array $data): void
    {
        $order = $this->findOrderByInvoiceId($data['invoice_id']);
        $advanceInvoice = $order->getAdvanceInvoiceFromCollection();
        $finalInvoice = $order->getFinalInvoiceFromCollection();

        if ($advanceInvoice !== null && $advanceInvoice->getFakturowniaId() === $invoiceId) {
            $this->handleAdvanceInvoicePaid($order, $paidAmount, $data);
        } elseif ($finalInvoice !== null && $finalInvoice->getFakturowniaId() === $invoiceId) {
            $this->handleFinalInvoicePaid($order, $paidAmount, $data);
        }
    }

    // RESPONSIBILITY #2: Payment status transitions (UNPAID → PAID)
    private function handleAdvanceInvoicePaid(Order $order, float $paidAmount, array $webhookData): void
    {
        $advancePayments = $this->paymentRepository->findBy([
            'order' => $order,
            'type' => PaymentType::DEPOSIT
        ]);

        foreach ($advancePayments as $payment) {
            if ($payment->getStatus() !== PaymentStatus::PAID && abs($payment->getAmount() - $paidAmount) < 0.01) {
                $payment->setStatus(PaymentStatus::PAID);
                $payment->setFakturowniaSyncedAt(new \DateTimeImmutable());
                break;
            }
        }

        $this->maybeMarkOrderAsCompletedWhenFullyPaid($order); // Jumps to responsibility #3
        $this->entityManager->flush();
    }

    // RESPONSIBILITY #3: Order completion workflow with complex business rules
    private function maybeMarkOrderAsCompletedWhenFullyPaid(Order $order): void
    {
        $hasAdvance = $order->hasAdvanceInvoiceInCollection();
        $hasFinal = $order->hasFinalInvoiceInCollection();
        $isAdvancePaid = $this->hasCompletedPaymentOfType($order, PaymentType::DEPOSIT);
        $isFinalPaid = $this->hasCompletedPaymentOfType($order, PaymentType::FINAL_PAYMENT);
        $fullyCovered = abs($order->getAmountDue()) < 0.01;

        if ($isAdvancePaid && $isFinalPaid && $fullyCovered) {
            $completedStatus = $this->orderStatusRepository->findOneBy(['code' => 'COMPLETED']);
            $order->setStatus($completedStatus);
            $order->setCompletedDate(new \DateTimeImmutable());
        }
    }
}
```

**Why it violates SRP:** This class has 3 independent reasons to change:
- Webhook format/routing changes → modify webhook parsing
- Payment status rules change → modify payment handling
- Order completion rules change → modify completion logic

Each should be a separate service: `WebhookEventRouter`, `PaymentStatusService`, `OrderCompletionService`.

## Rationale

1. **Maintainability**: Changes to one concern don't risk breaking unrelated behavior in the same class.

2. **Testability**: Small, focused classes are easy to unit test without complex mocking.

3. **Readability**: A class with a single purpose is easier to understand and onboard new developers.

4. **Reusability**: Focused classes can be reused in different contexts without dragging unrelated dependencies.

5. **Reduced Merge Conflicts**: When multiple developers work on different concerns, they won't collide on the same file.
