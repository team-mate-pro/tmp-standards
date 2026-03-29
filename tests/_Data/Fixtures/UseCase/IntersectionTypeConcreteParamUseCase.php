<?php

declare(strict_types=1);

namespace TeamMatePro\TmpStandards\Tests\_Data\Fixtures\UseCase;

/**
 * Invalid UseCase: __invoke() has intersection type with concrete class.
 */
final readonly class IntersectionTypeConcreteParamUseCase
{
    public function __invoke(ConcreteDto&SomeDtoInterface $dto): void
    {
    }
}
