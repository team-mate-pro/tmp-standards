<?php

declare(strict_types=1);

namespace TeamMatePro\TmpStandards\Tests\_Data\Fixtures\Logger;

use Psr\Log\LoggerInterface;

final readonly class InvalidWrongExceptionKey
{
    public function __construct(private LoggerInterface $logger)
    {
    }

    public function logUnderErrorKey(\Throwable $e): void
    {
        $this->logger->error('Operation failed', ['error' => $e]);
    }

    public function logUnderThrowableKey(\Exception $e): void
    {
        $this->logger->critical('Boom', ['throwable' => $e]);
    }

    public function logUnderShortKey(\Throwable $e): void
    {
        $this->logger->warning('Retryable failure', ['e' => $e]);
    }

    public function logWithNumericKey(\Throwable $e): void
    {
        $this->logger->error('Operation failed', [$e]);
    }

    public function logViaLogMethodWrongKey(\Throwable $e): void
    {
        $this->logger->log('error', 'Operation failed', ['ex' => $e]);
    }
}
