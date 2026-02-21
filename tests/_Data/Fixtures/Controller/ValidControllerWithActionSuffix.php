<?php

declare(strict_types=1);

namespace TeamMatePro\TmpStandards\Tests\_Data\Fixtures\Controller;

use TeamMatePro\UseCaseBundle\Http\RestApi\AbstractRestApiController;

/**
 * Valid: all public methods have the "Action" suffix.
 */
final class ValidControllerWithActionSuffix extends AbstractRestApiController
{
    public function getShopAction(): void
    {
    }

    public function createOrderAction(): void
    {
    }

    private function logPayload(): void
    {
    }
}
