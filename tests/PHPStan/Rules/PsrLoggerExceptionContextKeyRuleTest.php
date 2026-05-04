<?php

declare(strict_types=1);

namespace TeamMatePro\TmpStandards\Tests\PHPStan\Rules;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use TeamMatePro\TmpStandards\PHPStan\Rules\PsrLoggerExceptionContextKeyRule;

/**
 * @extends RuleTestCase<PsrLoggerExceptionContextKeyRule>
 */
#[CoversClass(PsrLoggerExceptionContextKeyRule::class)]
final class PsrLoggerExceptionContextKeyRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return new PsrLoggerExceptionContextKeyRule();
    }

    public function testValidExceptionKeyUsage(): void
    {
        $this->analyse(
            [__DIR__ . '/../../_Data/Fixtures/Logger/ValidExceptionKeyUsage.php'],
            []
        );
    }

    public function testInvalidWrongExceptionKey(): void
    {
        $tip = "\n    💡 See: https://github.com/team-mate-pro/tmp-standards/blob/main/definitions/clean-code/CC-003-psr3-exception-logging.md";

        $this->analyse(
            [__DIR__ . '/../../_Data/Fixtures/Logger/InvalidWrongExceptionKey.php'],
            [
                [
                    "Throwable passed to PSR-3 logger context under key 'error'; per PSR-3 §1.3 it MUST be under the 'exception' key." . $tip,
                    17,
                ],
                [
                    "Throwable passed to PSR-3 logger context under key 'throwable'; per PSR-3 §1.3 it MUST be under the 'exception' key." . $tip,
                    22,
                ],
                [
                    "Throwable passed to PSR-3 logger context under key 'e'; per PSR-3 §1.3 it MUST be under the 'exception' key." . $tip,
                    27,
                ],
                [
                    "Throwable passed to PSR-3 logger context under key (numeric); per PSR-3 §1.3 it MUST be under the 'exception' key." . $tip,
                    32,
                ],
                [
                    "Throwable passed to PSR-3 logger context under key 'ex'; per PSR-3 §1.3 it MUST be under the 'exception' key." . $tip,
                    37,
                ],
                [
                    "Throwable passed to PSR-3 logger context under key (dynamic); per PSR-3 §1.3 it MUST be under the 'exception' key." . $tip,
                    42,
                ],
            ]
        );
    }
}
