<?php

declare(strict_types=1);

namespace TeamMatePro\TmpStandards\Tests\_Data\Fixtures\Logger;

final class NonLoggerService
{
    /**
     * @param array<string, mixed> $context
     */
    public function error(string $message, array $context): void
    {
    }
}
