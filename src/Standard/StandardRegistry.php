<?php

declare(strict_types=1);

namespace TeamMatePro\TmpStandards\Standard;

final class StandardRegistry
{
    private const PHPSTAN_IDS = ['UCB-001', 'UCB-002', 'UCB-005'];

    /** @var array<string, StandardDefinition> */
    private array $standards = [];

    private bool $loaded = false;

    private readonly string $definitionsDir;

    public function __construct()
    {
        $this->definitionsDir = dirname(__DIR__, 2) . '/definitions';
    }

    public function get(string $id): StandardDefinition
    {
        $standards = $this->all();
        $normalizedId = strtoupper($id);

        if (!isset($standards[$normalizedId])) {
            throw new \InvalidArgumentException(sprintf(
                'Standard "%s" not found. Available: %s',
                $id,
                implode(', ', array_keys($standards)),
            ));
        }

        return $standards[$normalizedId];
    }

    /**
     * @return array<string, StandardDefinition>
     */
    public function all(): array
    {
        if (!$this->loaded) {
            $this->scan($this->definitionsDir);
            ksort($this->standards);
            $this->loaded = true;
        }

        return $this->standards;
    }

    private function scan(string $dir): void
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
        );

        /** @var \SplFileInfo $file */
        foreach ($iterator as $file) {
            if ($file->getExtension() !== 'md') {
                continue;
            }

            $filename = $file->getBasename('.md');

            if (!preg_match('/^([A-Z]+-\d+)/', $filename, $matches)) {
                continue;
            }

            $id = $matches[1];
            $basePath = $file->getPath() . '/' . $filename;
            $name = (string) preg_replace('/^[A-Z]+-\d+-/', '', $filename);

            $scriptPath = $basePath . '.sh';
            $promptPath = $basePath . '.prompt.txt';

            if (file_exists($scriptPath)) {
                $this->addPath($id, $name, CheckType::Script, $file->getPathname(), $scriptPath);
            } elseif (file_exists($promptPath)) {
                $this->addPath($id, $name, CheckType::Prompt, $file->getPathname(), $promptPath);
            } elseif (in_array($id, self::PHPSTAN_IDS, true)) {
                $this->addPath($id, $name, CheckType::Phpstan, $file->getPathname(), null);
            } else {
                $this->addPath($id, $name, CheckType::Manual, $file->getPathname(), null);
            }
        }
    }

    private function addPath(string $id, string $name, CheckType $checkType, string $definitionPath, ?string $checkPath): void
    {
        if (isset($this->standards[$id])) {
            if ($checkPath !== null) {
                $existing = $this->standards[$id];
                $this->standards[$id] = new StandardDefinition(
                    $id,
                    $existing->name,
                    $existing->checkType,
                    $existing->definitionPath,
                    [...$existing->checkPaths, $checkPath],
                );
            }

            return;
        }

        $this->standards[$id] = new StandardDefinition(
            $id,
            $name,
            $checkType,
            $definitionPath,
            $checkPath !== null ? [$checkPath] : [],
        );
    }
}
