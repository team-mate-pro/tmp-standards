<?php

declare(strict_types=1);

namespace TeamMatePro\TmpStandards\Tests\_Data\Fixtures\Logger;

use Psr\Log\LoggerInterface;

final readonly class ValidExceptionKeyUsage
{
    public function __construct(private LoggerInterface $logger)
    {
    }

    public function logError(\Throwable $e): void
    {
        $this->logger->error('Operation failed', ['exception' => $e, 'userId' => 42]);
    }

    public function logWarning(\Exception $e): void
    {
        $this->logger->warning('Retryable failure', ['exception' => $e]);
    }

    public function logViaLogMethod(\Throwable $e): void
    {
        $this->logger->log('error', 'Operation failed', ['exception' => $e]);
    }

    public function logNamed(\Throwable $e): void
    {
        $this->logger->error(message: 'Operation failed', context: ['exception' => $e]);
    }

    public function contextWithoutThrowableIsFine(): void
    {
        $this->logger->info('Something happened', ['userId' => 42, 'orderId' => 'ORD-1']);
    }

    public function dynamicMethodNameIsSkipped(\Throwable $e): void
    {
        $name = 'error';
        $this->logger->{$name}('Operation failed', ['error' => $e]);
    }

    public function nonLoggerCallIsSkipped(\Throwable $e): void
    {
        $other = new NonLoggerService();
        $other->error('Operation failed', ['error' => $e]);
    }

    public function nonContextLoggerMethodIsSkipped(): void
    {
        $this->logger->getName();
    }

    public function contextAsVariableIsSkipped(\Throwable $e): void
    {
        $context = ['error' => $e];
        $this->logger->error('Operation failed', $context);
    }

    public function contextWithSpreadIsSkipped(\Throwable $e): void
    {
        $extra = ['userId' => 42];
        $this->logger->info('Mixed', ['exception' => $e, ...$extra]);
    }

    public function namedContextWithoutPositionalIsHandled(\Throwable $e): void
    {
        $this->logger->log(level: 'error', message: 'Operation failed', context: ['exception' => $e]);
    }

    public function positionalUnpackedContextIsSkipped(\Throwable $e): void
    {
        $args = ['Operation failed', ['error' => $e]];
        $this->logger->error(...$args);
    }

    /**
     * @param array<int, mixed> $tail
     */
    public function spreadAtContextPositionIsSkipped(array $tail): void
    {
        $this->logger->error('Operation failed', ...$tail);
    }

    public function namedNonContextArgAtPositionIsSkipped(): void
    {
        $this->logger->log('error', 'Operation failed', other: []);
    }
}
