<?php

declare(strict_types=1);

namespace TeamMatePro\TmpStandards\Tests\_Data\Fixtures\Controller;

use TeamMatePro\UseCaseBundle\Http\RestApi\AbstractRestApiController;

/**
 * Mixed: one valid public method, one invalid, and exempt private/magic/static methods.
 */
final class ControllerWithMixedMethods extends AbstractRestApiController
{
    public function __construct()
    {
    }

    public function getShopAction(): void
    {
    }

    public function createOrder(): void
    {
    }

    private function helper(): void
    {
    }

    protected function internalHelper(): void
    {
    }

    public static function factory(): void
    {
    }
}
