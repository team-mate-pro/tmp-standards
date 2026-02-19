<?php

declare(strict_types=1);

namespace TeamMatePro\TmpStandards\Tests\_Data\Fixtures\UseCase;

/**
 * Invalid UseCase: __invoke() has concrete class parameter instead of interface.
 */
final readonly class ConcreteClassParamUseCase
{
    public function __invoke(ConcreteDto $dto): void
    {
    }
}
