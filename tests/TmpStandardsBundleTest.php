<?php

declare(strict_types=1);

namespace TeamMatePro\TmpStandards\Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use TeamMatePro\TmpStandards\TmpStandardsBundle;

#[CoversClass(TmpStandardsBundle::class)]
final class TmpStandardsBundleTest extends TestCase
{
    public function testBundleCanBeInstantiated(): void
    {
        $bundle = new TmpStandardsBundle();

        self::assertSame('TmpStandardsBundle', $bundle->getName());
    }
}
