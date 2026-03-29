<?php

declare(strict_types=1);

namespace TeamMatePro\TmpStandards\Standard;

final readonly class StandardDefinition
{
    /**
     * @param list<string> $checkPaths Paths to .sh or .prompt.txt files
     */
    public function __construct(
        public string $id,
        public string $name,
        public CheckType $checkType,
        public string $definitionPath,
        public array $checkPaths = [],
    ) {
    }
}
