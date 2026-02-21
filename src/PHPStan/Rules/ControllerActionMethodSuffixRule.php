<?php

declare(strict_types=1);

namespace TeamMatePro\TmpStandards\PHPStan\Rules;

use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;

/**
 * Ensures that public methods in controllers extending AbstractRestApiController
 * have the "Action" suffix.
 *
 * @see UCB-005: Controller Action Methods Must Have "Action" Suffix
 *
 * @implements Rule<Class_>
 */
final class ControllerActionMethodSuffixRule implements Rule
{
    private const ABSTRACT_CONTROLLER_CLASS = 'TeamMatePro\UseCaseBundle\Http\RestApi\AbstractRestApiController';

    public function __construct(
        private readonly ReflectionProvider $reflectionProvider,
    ) {
    }

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

        if ($node->isAbstract()) {
            return [];
        }

        $className = $scope->getNamespace() !== null
            ? $scope->getNamespace() . '\\' . $node->name->toString()
            : $node->name->toString();

        if (!$this->extendsAbstractRestApiController($className)) {
            return [];
        }

        $errors = [];

        foreach ($node->getMethods() as $method) {
            if (!$method->isPublic()) {
                continue;
            }

            if ($method->isStatic()) {
                continue;
            }

            $methodName = $method->name->toString();

            if (str_starts_with($methodName, '__')) {
                continue;
            }

            if (str_ends_with($methodName, 'Action')) {
                continue;
            }

            $errors[] = RuleErrorBuilder::message(
                sprintf(
                    'Controller method "%s::%s()" must have the "Action" suffix (e.g. "%sAction").',
                    $node->name->toString(),
                    $methodName,
                    $methodName,
                )
            )->identifier('controller.actionMethodSuffix')->line($method->getStartLine())->build();
        }

        return $errors;
    }

    private function extendsAbstractRestApiController(string $className): bool
    {
        if (!$this->reflectionProvider->hasClass($className)) {
            return false;
        }

        $classReflection = $this->reflectionProvider->getClass($className);

        return $classReflection->isSubclassOf(self::ABSTRACT_CONTROLLER_CLASS);
    }
}
