<?php

declare(strict_types=1);

namespace TeamMatePro\TmpStandards;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

final class TmpStandardsBundle extends AbstractBundle
{
    /** @param array<string, mixed> $config */
    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $services = $container->services()
            ->defaults()
                ->autowire()
                ->autoconfigure();

        $services->load('TeamMatePro\\TmpStandards\\Command\\', '../src/Command/');
        $services->load('TeamMatePro\\TmpStandards\\Standard\\', '../src/Standard/');
    }
}
