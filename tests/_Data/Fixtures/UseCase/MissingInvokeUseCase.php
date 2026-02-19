<?php

declare(strict_types=1);

namespace TeamMatePro\TmpStandards\Tests\_Data\Fixtures\UseCase;

/**
 * Invalid UseCase: missing __invoke() method.
 */
final readonly class MissingInvokeUseCase
{
    public function execute(SomeDtoInterface $dto): void
    {
    }
}
