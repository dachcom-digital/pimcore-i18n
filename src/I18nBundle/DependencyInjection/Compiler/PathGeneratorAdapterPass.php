<?php

namespace I18nBundle\DependencyInjection\Compiler;

use I18nBundle\Registry\PathGeneratorRegistry;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

final class PathGeneratorAdapterPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        foreach ($container->findTaggedServiceIds('i18n.adapter.path.generator', true) as $id => $tags) {
            $definition = $container->getDefinition(PathGeneratorRegistry::class);
            foreach ($tags as $attributes) {
                $definition->addMethodCall('register', [new Reference($id), $attributes['alias']]);
            }
        }
    }
}