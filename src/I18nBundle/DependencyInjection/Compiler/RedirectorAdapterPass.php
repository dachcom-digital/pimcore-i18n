<?php

namespace I18nBundle\DependencyInjection\Compiler;

use I18nBundle\Registry\RedirectorRegistry;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

final class RedirectorAdapterPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $services = [];
        $definition = $container->getDefinition(RedirectorRegistry::class);
        $registryAvailability = $container->getParameter('i18n.registry_availability');

        foreach ($container->findTaggedServiceIds('i18n.adapter.redirector', true) as $serviceId => $attributes) {
            $priority = isset($attributes[0]['priority']) ? $attributes[0]['priority'] : 0;
            $alias = isset($attributes[0]['alias']) ? $attributes[0]['alias'] : null;
            $serviceDefinition = $container->getDefinition($serviceId);
            $services[$priority][] = [
                'reference'  => new Reference($serviceId),
                'definition' => $serviceDefinition,
                'alias'      => $alias
            ];
        }

        if ($services) {
            krsort($services);
            $services = call_user_func_array('array_merge', $services);
        }

        foreach ($services as $service) {
            $serviceAlias = $service['alias'];
            $available = isset($registryAvailability['redirector'][$serviceAlias]) ? $registryAvailability['redirector'][$serviceAlias]['enabled'] : true;
            $definition->addMethodCall('register', [$service['reference'], $serviceAlias]);

            $service['definition']->addMethodCall('setName', [$serviceAlias]);
            $service['definition']->addMethodCall('setEnabled', [$available]);
        }
    }
}
