<?php

declare(strict_types=1);

namespace TeamMatePro\TmpStandards\Tests\_Data\Fixtures\Controller;

use TeamMatePro\UseCaseBundle\Http\RestApi\AbstractRestApiController;

/**
 * Abstract controller extending AbstractRestApiController — should be exempt.
 */
abstract class AbstractExampleController extends AbstractRestApiController
{
    public function listItems(): void
    {
    }
}
