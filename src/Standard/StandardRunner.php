<?php

declare(strict_types=1);

namespace TeamMatePro\TmpStandards\Standard;

use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Process\Process;

final class StandardRunner
{
    private ?bool $phpstanResult = null;

    public function run(StandardDefinition $standard, SymfonyStyle $io, string $projectDir): bool
    {
        return match ($standard->checkType) {
            CheckType::Script => $this->runScript($standard, $io, $projectDir),
            CheckType::Prompt => $this->runPrompt($standard, $io, $projectDir),
            CheckType::Phpstan => $this->runPhpstan($standard, $io, $projectDir),
            CheckType::Manual => $this->runManual($standard, $io),
        };
    }

    private function runScript(StandardDefinition $standard, SymfonyStyle $io, string $projectDir): bool
    {
        $passed = true;

        foreach ($standard->checkPaths as $scriptPath) {
            $io->text(sprintf('Running script: <comment>%s</comment>', basename($scriptPath)));

            $process = new Process(['bash', $scriptPath], $projectDir);
            $process->setTimeout(120);
            $exitCode = $this->execute($process, $io);

            if ($exitCode !== 0) {
                $passed = false;
            }
        }

        return $passed;
    }

    private function runPrompt(StandardDefinition $standard, SymfonyStyle $io, string $projectDir): bool
    {
        if (!$this->isClaudeAvailable()) {
            $io->error('Claude CLI is not available. Install it to run prompt-based checks.');

            return false;
        }

        $passed = true;

        foreach ($standard->checkPaths as $promptPath) {
            $io->text(sprintf('Running AI review: <comment>%s</comment>', basename($promptPath)));

            $prompt = file_get_contents($promptPath);
            $process = new Process(['claude', '-p', $prompt, '--cwd', $projectDir], $projectDir);
            $process->setTimeout(300);
            $exitCode = $this->execute($process, $io);

            if ($exitCode !== 0) {
                $passed = false;
            }
        }

        return $passed;
    }

    private function runPhpstan(StandardDefinition $standard, SymfonyStyle $io, string $projectDir): bool
    {
        if ($this->phpstanResult !== null) {
            $io->text(sprintf(
                'PHPStan already executed — reusing result for <info>%s</info>',
                $standard->id,
            ));

            return $this->phpstanResult;
        }

        $io->text('Running PHPStan analysis...');

        $process = new Process(['composer', 'phpstan'], $projectDir);
        $process->setTimeout(300);
        $exitCode = $this->execute($process, $io);

        $this->phpstanResult = $exitCode === 0;

        return $this->phpstanResult;
    }

    private function runManual(StandardDefinition $standard, SymfonyStyle $io): bool
    {
        $io->warning(sprintf(
            'Standard %s (%s) requires manual review — skipping.',
            $standard->id,
            $standard->name,
        ));

        return true;
    }

    private function execute(Process $process, SymfonyStyle $io): int
    {
        $process->run(static function (string $type, string $buffer) use ($io): void {
            $io->write($buffer);
        });

        return $process->getExitCode() ?? 1;
    }

    private function isClaudeAvailable(): bool
    {
        $process = new Process(['which', 'claude']);
        $process->run();

        return $process->isSuccessful();
    }
}
