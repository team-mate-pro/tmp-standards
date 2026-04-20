<?php

declare(strict_types=1);

namespace TeamMatePro\TmpStandards\PHPStan\Rules;

use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Scalar\String_;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\ObjectType;
use Psr\Log\LoggerInterface;

/**
 * Ensures that a \Throwable passed to a PSR-3 logger context is under the reserved 'exception' key.
 *
 * @see CC-003: PSR-3 Exception Logging Context
 *
 * @implements Rule<MethodCall>
 */
final class PsrLoggerExceptionContextKeyRule implements Rule
{
    private const CONTEXT_INDEX_BY_METHOD = [
        'emergency' => 1,
        'alert'     => 1,
        'critical'  => 1,
        'error'     => 1,
        'warning'   => 1,
        'notice'    => 1,
        'info'      => 1,
        'debug'     => 1,
        'log'       => 2,
    ];

    public function getNodeType(): string
    {
        return MethodCall::class;
    }

    /**
     * @param MethodCall $node
     */
    public function processNode(Node $node, Scope $scope): array
    {
        if (!$node->name instanceof Node\Identifier) {
            return [];
        }

        $methodName = $node->name->toString();

        if (!isset(self::CONTEXT_INDEX_BY_METHOD[$methodName])) {
            return [];
        }

        $callerType = $scope->getType($node->var);
        $loggerType = new ObjectType(LoggerInterface::class);

        if (!$loggerType->isSuperTypeOf($callerType)->yes()) {
            return [];
        }

        $contextArg = $this->findContextArg($node->args, self::CONTEXT_INDEX_BY_METHOD[$methodName]);

        if ($contextArg === null || !$contextArg->value instanceof Array_) {
            return [];
        }

        $throwableType = new ObjectType(\Throwable::class);
        $errors = [];

        foreach ($contextArg->value->items as $item) {
            if ($item->unpack) {
                continue;
            }

            if (!$throwableType->isSuperTypeOf($scope->getType($item->value))->yes()) {
                continue;
            }

            if ($this->isExceptionKey($item->key)) {
                continue;
            }

            $errors[] = RuleErrorBuilder::message(
                sprintf(
                    'Throwable passed to PSR-3 logger context under key %s; per PSR-3 §1.3 it MUST be under the \'exception\' key.',
                    $this->describeKey($item->key)
                )
            )
                ->identifier('logger.exceptionContextKey')
                ->tip('See: https://github.com/team-mate-pro/tmp-standards/blob/main/definitions/clean-code/CC-003-psr3-exception-logging.md')
                ->line($item->getStartLine())
                ->build();
        }

        return $errors;
    }

    /**
     * @param array<Arg|Node\VariadicPlaceholder> $args
     */
    private function findContextArg(array $args, int $positionalIndex): ?Arg
    {
        foreach ($args as $arg) {
            if ($arg instanceof Arg && $arg->name !== null && $arg->name->toString() === 'context') {
                return $arg;
            }
        }

        if (!isset($args[$positionalIndex]) || !$args[$positionalIndex] instanceof Arg) {
            return null;
        }

        if ($args[$positionalIndex]->unpack) {
            return null;
        }

        if ($args[$positionalIndex]->name !== null) {
            return null;
        }

        return $args[$positionalIndex];
    }

    private function isExceptionKey(?Node\Expr $key): bool
    {
        return $key instanceof String_ && $key->value === 'exception';
    }

    private function describeKey(?Node\Expr $key): string
    {
        if ($key === null) {
            return '(numeric)';
        }

        if ($key instanceof String_) {
            return sprintf("'%s'", $key->value);
        }

        return '(dynamic)';
    }
}
