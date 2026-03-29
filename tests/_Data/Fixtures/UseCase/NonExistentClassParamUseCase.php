<?php

declare(strict_types=1);

namespace TeamMatePro\TmpStandards\Tests\_Data\Fixtures\UseCase;

/**
 * Valid UseCase: __invoke() references a class that doesn't exist in reflection.
 */
final readonly class NonExistentClassParamUseCase
{
    public function __invoke(\NonExistent\SomeClass $data): void
    {
    }
}
