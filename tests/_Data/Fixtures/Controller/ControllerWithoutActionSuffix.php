<?php

declare(strict_types=1);

namespace TeamMatePro\TmpStandards\Tests\_Data\Fixtures\Controller;

use TeamMatePro\UseCaseBundle\Http\RestApi\AbstractRestApiController;

/**
 * Invalid: public methods are missing the "Action" suffix.
 */
final class ControllerWithoutActionSuffix extends AbstractRestApiController
{
    public function importAllExternalCustomers(): void
    {
    }

    public function externalCustomerLookup(): void
    {
    }
}
