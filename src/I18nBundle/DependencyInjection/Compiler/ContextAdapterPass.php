<?php

namespace I18nBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\PriorityTaggedServiceTrait;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class ContextAdapterPass implements CompilerPassInterface
{
    use PriorityTaggedServiceTrait;

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        foreach ($this->findAndSortTaggedServices('i18n.adapter.context', $container) as $id => $reference) {
            $container->getDefinition('i18n.registry.context')->addMethodCall('register', [(string)$reference, $reference]);
        }
    }
}