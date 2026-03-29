<?php

declare(strict_types=1);

namespace TeamMatePro\TmpStandards\Standard;

final class SuiteRegistry
{
    /** @var array<string, list<string>> */
    private const SUITES = [
        'php-sf-app' => [
            'ARCH-001', 'ARCH-002',
            'CC-001',
            'SOLID-001', 'SOLID-002', 'SOLID-003', 'SOLID-004', 'SOLID-005',
            'INF-001', 'INF-002', 'INF-003',
            'TEST-001', 'TEST-002',
            'UCB-001', 'UCB-002', 'UCB-003', 'UCB-004', 'UCB-005', 'UCB-006',
        ],
        'php-lib' => [
            'CC-001',
            'SOLID-001', 'SOLID-002', 'SOLID-003', 'SOLID-004', 'SOLID-005',
            'TEST-001', 'TEST-002',
        ],
        'frontend' => [
            'FE-001', 'FE-002', 'FE-003',
        ],
        'infra' => [
            'INF-001', 'INF-002', 'INF-003', 'INF-004',
        ],
    ];

    /**
     * @return list<string>
     */
    public function get(string $name): array
    {
        if (!isset(self::SUITES[$name])) {
            throw new \InvalidArgumentException(sprintf(
                'Suite "%s" not found. Available: %s',
                $name,
                implode(', ', array_keys(self::SUITES)),
            ));
        }

        return self::SUITES[$name];
    }

    /**
     * @return list<string>
     */
    public function list(): array
    {
        return array_keys(self::SUITES);
    }
}
