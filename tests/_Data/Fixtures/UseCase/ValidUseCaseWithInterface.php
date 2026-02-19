<?php

declare(strict_types=1);

namespace TeamMatePro\TmpStandards\Tests\_Data\Fixtures\UseCase;

/**
 * Valid UseCase: has __invoke() with interface parameter.
 */
final readonly class ValidUseCaseWithInterface
{
    public function __invoke(SomeDtoInterface $dto): void
    {
    }
}
