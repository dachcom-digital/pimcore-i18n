<?php

namespace I18nBundle\DependencyInjection\Compiler;

use I18nBundle\Builder\RouteItemBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

final class RouterPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        /** @phpstan-ignore-next-line */
        if ($container->has('router.default')) {
            $container->getDefinition(RouteItemBuilder::class)->addMethodCall('setFrameworkRouter', [new Reference('router.default')]);
        }
    }
}
