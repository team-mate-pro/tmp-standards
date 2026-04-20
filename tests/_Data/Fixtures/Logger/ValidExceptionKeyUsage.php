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
}
