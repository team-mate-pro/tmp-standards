<?php

declare(strict_types=1);

namespace TeamMatePro\TmpStandards\Tests\_Data\Fixtures\UseCase;

/**
 * Abstract UseCase: should be exempt from rules.
 */
abstract class AbstractUseCase
{
    // No __invoke() required - abstract classes are exempt
}
