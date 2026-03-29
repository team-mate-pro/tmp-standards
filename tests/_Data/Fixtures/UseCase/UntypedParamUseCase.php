<?php

declare(strict_types=1);

namespace TeamMatePro\TmpStandards\Tests\_Data\Fixtures\UseCase;

/**
 * Valid UseCase: __invoke() has untyped parameter.
 */
final readonly class UntypedParamUseCase
{
    public function __invoke($data): void
    {
    }
}
