<?php

declare(strict_types=1);

namespace TeamMatePro\TmpStandards\PHPStan\Rules;

use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * Ensures that every class ending with "UseCase" has an __invoke() method.
 *
 * @see UCB-002: UseCase Must Have Invoke Method
 *
 * @implements Rule<Class_>
 */
final class UseCaseMustHaveInvokeMethodRule implements Rule
{
    public function getNodeType(): string
    {
        return Class_::class;
    }

    /**
     * @param Class_ $node
     */
    public function processNode(Node $node, Scope $scope): array
    {
        if ($node->name === null) {
            return [];
        }

        $className = $node->name->toString();

        if (!str_ends_with($className, 'UseCase')) {
            return [];
        }

        if ($node->isAbstract()) {
            return [];
        }

        foreach ($node->getMethods() as $method) {
            if ($method->name->toString() === '__invoke') {
                return [];
            }
        }

        return [
            RuleErrorBuilder::message(
                sprintf('UseCase class "%s" must have an __invoke method.', $className)
            )
                ->identifier('useCase.missingInvoke')
                ->tip('See: https://github.com/team-mate-pro/tmp-standards/blob/main/definitions/use-case-bundle/UCB-002-use-case-invoke-method.md')
                ->build(),
        ];
    }
}
