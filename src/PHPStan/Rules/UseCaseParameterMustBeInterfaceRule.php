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
 * Ensures that UseCase __invoke() parameters are interfaces or scalar types, not concrete classes.
 *
 * @see UCB-001: UseCase Parameters Must Be Interfaces
 *
 * @implements Rule<Class_>
 */
final class UseCaseParameterMustBeInterfaceRule implements Rule
{
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

        $className = $node->name->toString();

        if (!str_ends_with($className, 'UseCase')) {
            return [];
        }

        if ($node->isAbstract()) {
            return [];
        }

        $invokeMethod = null;
        foreach ($node->getMethods() as $method) {
            if ($method->name->toString() === '__invoke') {
                $invokeMethod = $method;
                break;
            }
        }

        if ($invokeMethod === null) {
            return [];
        }

        $errors = [];

        foreach ($invokeMethod->params as $param) {
            if ($param->type === null) {
                continue;
            }

            $typeNode = $param->type;

            // Handle nullable types
            if ($typeNode instanceof Node\NullableType) {
                $typeNode = $typeNode->type;
            }

            // Handle union types - check each type
            if ($typeNode instanceof Node\UnionType) {
                foreach ($typeNode->types as $unionType) {
                    $error = $this->checkType($unionType, $param, $className, $scope);
                    if ($error !== null) {
                        $errors[] = $error;
                    }
                }
                continue;
            }

            // Handle intersection types - check each type
            if ($typeNode instanceof Node\IntersectionType) {
                foreach ($typeNode->types as $intersectionType) {
                    $error = $this->checkType($intersectionType, $param, $className, $scope);
                    if ($error !== null) {
                        $errors[] = $error;
                    }
                }
                continue;
            }

            $error = $this->checkType($typeNode, $param, $className, $scope);
            if ($error !== null) {
                $errors[] = $error;
            }
        }

        return $errors;
    }

    private function checkType(Node $typeNode, Node\Param $param, string $className, Scope $scope): ?\PHPStan\Rules\RuleError
    {
        // Skip built-in types (scalars, void, etc.)
        if ($typeNode instanceof Node\Identifier) {
            return null;
        }

        if (!$typeNode instanceof Node\Name) {
            return null;
        }

        $typeName = $typeNode->toString();

        // Skip built-in types that might be represented as Name nodes
        $builtinTypes = ['string', 'int', 'float', 'bool', 'array', 'object', 'mixed', 'callable', 'iterable', 'null', 'void', 'never', 'true', 'false'];
        if (in_array(strtolower($typeName), $builtinTypes, true)) {
            return null;
        }

        // Resolve the fully qualified class name
        $resolvedName = $scope->resolveName($typeNode);

        if (!$this->reflectionProvider->hasClass($resolvedName)) {
            return null;
        }

        $classReflection = $this->reflectionProvider->getClass($resolvedName);

        // Allow interfaces
        if ($classReflection->isInterface()) {
            return null;
        }

        // Disallow concrete classes (including abstract classes)
        $paramName = $param->var instanceof Node\Expr\Variable && is_string($param->var->name)
            ? '$' . $param->var->name
            : '(unknown)';

        return RuleErrorBuilder::message(
            sprintf(
                'UseCase "%s" parameter %s must use an interface, not concrete class "%s".',
                $className,
                $paramName,
                $resolvedName
            )
        )->identifier('useCase.parameterMustBeInterface')->line($param->getStartLine())->build();
    }
}
