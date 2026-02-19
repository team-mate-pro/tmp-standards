<?php

declare(strict_types=1);

namespace TeamMatePro\TmpStandards\Tests\PHPStan\Rules;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use TeamMatePro\TmpStandards\PHPStan\Rules\UseCaseParameterMustBeInterfaceRule;

/**
 * @extends RuleTestCase<UseCaseParameterMustBeInterfaceRule>
 */
#[CoversClass(UseCaseParameterMustBeInterfaceRule::class)]
final class UseCaseParameterMustBeInterfaceRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return new UseCaseParameterMustBeInterfaceRule(
            self::createReflectionProvider()
        );
    }

    public function testValidUseCaseWithInterfaceParameter(): void
    {
        $this->analyse(
            [__DIR__ . '/../../_Data/Fixtures/UseCase/ValidUseCaseWithInterface.php'],
            []
        );
    }

    public function testValidUseCaseWithScalarParameters(): void
    {
        $this->analyse(
            [__DIR__ . '/../../_Data/Fixtures/UseCase/ValidUseCaseWithScalars.php'],
            []
        );
    }

    public function testValidUseCaseWithMixedParameters(): void
    {
        $this->analyse(
            [__DIR__ . '/../../_Data/Fixtures/UseCase/ValidUseCaseWithMixedParams.php'],
            []
        );
    }

    public function testInvalidUseCaseWithConcreteClassParameter(): void
    {
        $this->analyse(
            [__DIR__ . '/../../_Data/Fixtures/UseCase/ConcreteClassParamUseCase.php'],
            [
                [
                    'UseCase "ConcreteClassParamUseCase" parameter $dto must use an interface, not concrete class "TeamMatePro\TmpStandards\Tests\_Data\Fixtures\UseCase\ConcreteDto".',
                    12,
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
