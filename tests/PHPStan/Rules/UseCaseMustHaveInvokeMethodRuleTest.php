<?php

declare(strict_types=1);

namespace TeamMatePro\TmpStandards\Tests\PHPStan\Rules;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use TeamMatePro\TmpStandards\PHPStan\Rules\UseCaseMustHaveInvokeMethodRule;

/**
 * @extends RuleTestCase<UseCaseMustHaveInvokeMethodRule>
 */
#[CoversClass(UseCaseMustHaveInvokeMethodRule::class)]
final class UseCaseMustHaveInvokeMethodRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return new UseCaseMustHaveInvokeMethodRule();
    }

    public function testValidUseCaseWithInvokeMethod(): void
    {
        $this->analyse(
            [__DIR__ . '/../../_Data/Fixtures/UseCase/ValidUseCaseWithInterface.php'],
            []
        );
    }

    public function testValidUseCaseWithScalars(): void
    {
        $this->analyse(
            [__DIR__ . '/../../_Data/Fixtures/UseCase/ValidUseCaseWithScalars.php'],
            []
        );
    }

    public function testValidUseCaseWithMixedParams(): void
    {
        $this->analyse(
            [__DIR__ . '/../../_Data/Fixtures/UseCase/ValidUseCaseWithMixedParams.php'],
            []
        );
    }

    public function testInvalidUseCaseMissingInvoke(): void
    {
        $this->analyse(
            [__DIR__ . '/../../_Data/Fixtures/UseCase/MissingInvokeUseCase.php'],
            [
                [
                    'UseCase class "MissingInvokeUseCase" must have an __invoke method.',
                    10,
                ],
            ]
        );
    }

    public function testAbstractUseCaseIsExempt(): void
    {
        $this->analyse(
            [__DIR__ . '/../../_Data/Fixtures/UseCase/AbstractUseCase.php'],
            []
        );
    }

    public function testRegularClassNotAffected(): void
    {
        $this->analyse(
            [__DIR__ . '/../../_Data/Fixtures/UseCase/RegularService.php'],
            []
        );
    }
}
