<?php

namespace I18nBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\PriorityTaggedServiceTrait;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class PathGeneratorAdapterPass implements CompilerPassInterface
{
    use PriorityTaggedServiceTrait;

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        foreach ($this->findAndSortTaggedServices('i18n.adapter.path.generator', $container) as $id => $reference) {
            $container->getDefinition('i18n.registry.path.generator')->addMethodCall('register', [(string)$reference, $reference]);
        }
    }
}