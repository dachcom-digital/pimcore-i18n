<?php

namespace I18nBundle\DependencyInjection\Compiler;

use I18nBundle\Registry\LocaleProviderRegistry;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

final class LocaleProviderAdapterPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        foreach ($container->findTaggedServiceIds('i18n.adapter.locale', true) as $id => $tags) {
            $definition = $container->getDefinition(LocaleProviderRegistry::class);
            foreach ($tags as $attributes) {
                $definition->addMethodCall('register', [new Reference($id), $attributes['alias']]);
            }
        }
    }
}
