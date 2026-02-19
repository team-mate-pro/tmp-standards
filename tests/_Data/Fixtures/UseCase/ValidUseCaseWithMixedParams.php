<?php

declare(strict_types=1);

namespace TeamMatePro\TmpStandards\Tests\_Data\Fixtures\UseCase;

/**
 * Valid UseCase: has __invoke() with interface and scalar parameters.
 */
final readonly class ValidUseCaseWithMixedParams
{
    public function __invoke(SomeDtoInterface $dto, string $organizationId): void
    {
    }
}
