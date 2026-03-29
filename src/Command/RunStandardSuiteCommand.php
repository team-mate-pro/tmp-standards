<?php

declare(strict_types=1);

namespace TeamMatePro\TmpStandards\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use TeamMatePro\TmpStandards\Standard\StandardRegistry;
use TeamMatePro\TmpStandards\Standard\StandardRunner;
use TeamMatePro\TmpStandards\Standard\SuiteRegistry;

#[AsCommand(
    name: 'tmp:standard:suite',
    description: 'Run a predefined suite of coding standard checks',
)]
final class RunStandardSuiteCommand extends Command
{
    public function __construct(
        private readonly StandardRegistry $standardRegistry,
        private readonly SuiteRegistry $suiteRegistry,
        private readonly StandardRunner $runner,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('name', InputArgument::REQUIRED, sprintf(
                'Suite name (%s)',
                implode(', ', (new SuiteRegistry())->list()),
            ))
            ->addOption('project-dir', null, InputOption::VALUE_REQUIRED, 'Project root directory', getcwd());
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        /** @var string $suiteName */
        $suiteName = $input->getArgument('name');

        /** @var string $projectDir */
        $projectDir = $input->getOption('project-dir');

        try {
            $standardIds = $this->suiteRegistry->get($suiteName);
        } catch (\InvalidArgumentException $e) {
            $io->error($e->getMessage());

            return Command::FAILURE;
        }

        $io->title(sprintf('Running suite: %s (%d standards)', $suiteName, count($standardIds)));

        $results = [];
        $failed = false;

        foreach ($standardIds as $id) {
            try {
                $standard = $this->standardRegistry->get($id);
            } catch (\InvalidArgumentException) {
                $io->warning(sprintf('Standard %s not found — skipping.', $id));
                $results[] = [$id, '?', '<comment>NOT FOUND</comment>'];

                continue;
            }

            $io->section(sprintf('%s: %s [%s]', $standard->id, $standard->name, $standard->checkType->value));

            $passed = $this->runner->run($standard, $io, $projectDir);

            if (!$passed) {
                $failed = true;
            }

            $results[] = [
                $standard->id,
                $standard->checkType->value,
                $passed ? '<info>PASS</info>' : '<error>FAIL</error>',
            ];
        }

        $io->newLine();
        $io->section('Summary');
        $io->table(['ID', 'Type', 'Result'], $results);

        if ($failed) {
            $io->error(sprintf('Suite "%s" failed.', $suiteName));

            return Command::FAILURE;
        }

        $io->success(sprintf('Suite "%s" passed.', $suiteName));

        return Command::SUCCESS;
    }
}
