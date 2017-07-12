<?php

namespace I18nBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\PriorityTaggedServiceTrait;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class LanguageAdapterPass implements CompilerPassInterface
{
    use PriorityTaggedServiceTrait;

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        foreach ($this->findAndSortTaggedServices('i18n.adapter.language', $container) as $id => $reference) {
            $container->getDefinition('i18n.registry.language')->addMethodCall('register', [(string)$reference, $reference]);
        }
    }
}