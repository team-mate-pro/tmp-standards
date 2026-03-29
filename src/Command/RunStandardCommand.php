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

#[AsCommand(
    name: 'tmp:standard',
    description: 'Run a single coding standard check by ID',
)]
final class RunStandardCommand extends Command
{
    public function __construct(
        private readonly StandardRegistry $registry,
        private readonly StandardRunner $runner,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('id', InputArgument::REQUIRED, 'Standard ID (e.g., TEST-001, INF-001)')
            ->addOption('project-dir', null, InputOption::VALUE_REQUIRED, 'Project root directory', getcwd());
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        /** @var string $id */
        $id = $input->getArgument('id');

        /** @var string $projectDir */
        $projectDir = $input->getOption('project-dir');

        try {
            $standard = $this->registry->get($id);
        } catch (\InvalidArgumentException $e) {
            $io->error($e->getMessage());

            return Command::FAILURE;
        }

        $io->section(sprintf('%s: %s [%s]', $standard->id, $standard->name, $standard->checkType->value));

        $passed = $this->runner->run($standard, $io, $projectDir);

        $io->newLine();

        if ($passed) {
            $io->success(sprintf('%s passed.', $standard->id));

            return Command::SUCCESS;
        }

        $io->error(sprintf('%s failed.', $standard->id));

        return Command::FAILURE;
    }
}
