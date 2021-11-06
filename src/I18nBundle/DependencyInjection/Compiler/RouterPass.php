<?php

namespace I18nBundle\DependencyInjection\Compiler;

use I18nBundle\Manager\RouteItemManager;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

final class RouterPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if ($container->has('router.default')) {
            $container->getDefinition(RouteItemManager::class)->addMethodCall('setFrameworkRouter', [new Reference('router.default')]);
        }
    }
}
