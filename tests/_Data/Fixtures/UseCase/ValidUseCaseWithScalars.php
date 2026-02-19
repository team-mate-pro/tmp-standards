<?php

declare(strict_types=1);

namespace TeamMatePro\TmpStandards\Tests\_Data\Fixtures\UseCase;

/**
 * Valid UseCase: has __invoke() with scalar parameters.
 */
final readonly class ValidUseCaseWithScalars
{
    public function __invoke(string $userId, int $limit, ?bool $active = null): void
    {
    }
}
