<?php

declare(strict_types=1);

namespace TeamMatePro\TmpStandards\Tests\_Data\Fixtures\UseCase;

/**
 * Invalid UseCase: __invoke() has nullable concrete class parameter.
 */
final readonly class NullableConcreteParamUseCase
{
    public function __invoke(?ConcreteDto $dto): void
    {
    }
}
