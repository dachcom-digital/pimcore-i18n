<?php

namespace DachcomBundle\Test\DependencyInjection;

use Pimcore\Config;
use DachcomBundle\Test\App\Pimcore\TestConfig;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

class ServiceChangePass implements CompilerPassInterface
{
    /**
     * @param ContainerBuilder $container
     */
    public function process(ContainerBuilder $container)
    {
        $testService = new Definition(TestConfig::class);
        $testService->setPublic(true);

        $container->setDefinition(TestConfig::class, $testService);
        $container->getDefinition(Config::class)->setClass(TestConfig::class);
    }
}
