<?php

declare(strict_types=1);

namespace TeamMatePro\TmpStandards\Tests\PHPStan\Rules;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use TeamMatePro\TmpStandards\PHPStan\Rules\ControllerActionMethodSuffixRule;

/**
 * @extends RuleTestCase<ControllerActionMethodSuffixRule>
 */
#[CoversClass(ControllerActionMethodSuffixRule::class)]
final class ControllerActionMethodSuffixRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return new ControllerActionMethodSuffixRule(
            self::createReflectionProvider()
        );
    }

    public function testValidControllerWithActionSuffix(): void
    {
        $this->analyse(
            [__DIR__ . '/../../_Data/Fixtures/Controller/ValidControllerWithActionSuffix.php'],
            []
        );
    }

    public function testControllerWithoutActionSuffix(): void
    {
        $this->analyse(
            [__DIR__ . '/../../_Data/Fixtures/Controller/ControllerWithoutActionSuffix.php'],
            [
                [
                    'Controller method "ControllerWithoutActionSuffix::importAllExternalCustomers()" must have the "Action" suffix (e.g. "importAllExternalCustomersAction").',
                    14,
                ],
                [
                    'Controller method "ControllerWithoutActionSuffix::externalCustomerLookup()" must have the "Action" suffix (e.g. "externalCustomerLookupAction").',
                    18,
                ],
            ]
        );
    }

    public function testControllerWithMixedMethods(): void
    {
        $this->analyse(
            [__DIR__ . '/../../_Data/Fixtures/Controller/ControllerWithMixedMethods.php'],
            [
                [
                    'Controller method "ControllerWithMixedMethods::createOrder()" must have the "Action" suffix (e.g. "createOrderAction").',
                    22,
                ],
            ]
        );
    }

    public function testRegularControllerNotExtendingIsExempt(): void
    {
        $this->analyse(
            [__DIR__ . '/../../_Data/Fixtures/Controller/RegularControllerNotExtending.php'],
            []
        );
    }
}
