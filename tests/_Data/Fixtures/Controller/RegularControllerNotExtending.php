<?php

declare(strict_types=1);

namespace TeamMatePro\TmpStandards\Tests\_Data\Fixtures\Controller;

/**
 * Regular controller NOT extending AbstractRestApiController — rule should not apply.
 */
final class RegularControllerNotExtending
{
    public function getShop(): void
    {
    }

    public function createOrder(): void
    {
    }
}
