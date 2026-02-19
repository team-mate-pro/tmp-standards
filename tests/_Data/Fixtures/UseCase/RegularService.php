<?php

declare(strict_types=1);

namespace TeamMatePro\TmpStandards\Tests\_Data\Fixtures\UseCase;

/**
 * Regular class NOT ending with UseCase - rules should not apply.
 */
final class RegularService
{
    public function doSomething(ConcreteDto $dto): void
    {
    }
}
