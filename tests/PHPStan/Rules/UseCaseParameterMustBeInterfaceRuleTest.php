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
                    "UseCase \"ConcreteClassParamUseCase\" parameter \$dto must use an interface, not concrete class \"TeamMatePro\\TmpStandards\\Tests\\_Data\\Fixtures\\UseCase\\ConcreteDto\".\n    💡 See: https://github.com/team-mate-pro/tmp-standards/blob/main/definitions/use-case-bundle/UCB-001-use-case-abstract-dto.md",
                    12,
                    'See: https://github.com/team-mate-pro/tmp-standards/blob/main/definitions/use-case-bundle/UCB-001-use-case-abstract-dto.md',
                ],
            ]
        );
    }

    public function testInvalidUseCaseWithNullableConcreteClassParameter(): void
    {
        $this->analyse(
            [__DIR__ . '/../../_Data/Fixtures/UseCase/NullableConcreteParamUseCase.php'],
            [
                [
                    'UseCase "NullableConcreteParamUseCase" parameter $dto must use an interface, not concrete class "TeamMatePro\TmpStandards\Tests\_Data\Fixtures\UseCase\ConcreteDto".',
                    12,
                    'See: https://github.com/team-mate-pro/tmp-standards/blob/main/definitions/use-case-bundle/UCB-001-use-case-abstract-dto.md',
                ],
            ]
        );
    }

    public function testInvalidUseCaseWithUnionTypeConcreteClassParameter(): void
    {
        $this->analyse(
            [__DIR__ . '/../../_Data/Fixtures/UseCase/UnionTypeConcreteParamUseCase.php'],
            [
                [
                    'UseCase "UnionTypeConcreteParamUseCase" parameter $dto must use an interface, not concrete class "TeamMatePro\TmpStandards\Tests\_Data\Fixtures\UseCase\ConcreteDto".',
                    12,
                    'See: https://github.com/team-mate-pro/tmp-standards/blob/main/definitions/use-case-bundle/UCB-001-use-case-abstract-dto.md',
                ],
            ]
        );
    }

    public function testInvalidUseCaseWithIntersectionTypeConcreteClassParameter(): void
    {
        $this->analyse(
            [__DIR__ . '/../../_Data/Fixtures/UseCase/IntersectionTypeConcreteParamUseCase.php'],
            [
                [
                    'UseCase "IntersectionTypeConcreteParamUseCase" parameter $dto must use an interface, not concrete class "TeamMatePro\TmpStandards\Tests\_Data\Fixtures\UseCase\ConcreteDto".',
                    12,
                    'See: https://github.com/team-mate-pro/tmp-standards/blob/main/definitions/use-case-bundle/UCB-001-use-case-abstract-dto.md',
                ],
            ]
        );
    }

    public function testValidUseCaseWithUntypedParameter(): void
    {
        $this->analyse(
            [__DIR__ . '/../../_Data/Fixtures/UseCase/UntypedParamUseCase.php'],
            []
        );
    }

    public function testValidUseCaseWithNonExistentClassParameter(): void
    {
        $this->analyse(
            [__DIR__ . '/../../_Data/Fixtures/UseCase/NonExistentClassParamUseCase.php'],
            []
        );
    }

    public function testUseCaseWithoutInvokeMethodIsSkipped(): void
    {
        $this->analyse(
            [__DIR__ . '/../../_Data/Fixtures/UseCase/MissingInvokeUseCase.php'],
            []
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
