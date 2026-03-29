<?php

declare(strict_types=1);

namespace TeamMatePro\TmpStandards\Tests\_Data\Fixtures\UseCase;

/**
 * Invalid UseCase: __invoke() has union type with concrete class.
 */
final readonly class UnionTypeConcreteParamUseCase
{
    public function __invoke(ConcreteDto|string $dto): void
    {
    }
}
