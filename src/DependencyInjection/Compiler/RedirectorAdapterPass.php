<?php

/*
 * This source file is available under two different licenses:
 *   - GNU General Public License version 3 (GPLv3)
 *   - DACHCOM Commercial License (DCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) DACHCOM.DIGITAL AG (https://www.dachcom-digital.com)
 * @license    GPLv3 and DCL
 */

namespace I18nBundle\DependencyInjection\Compiler;

use I18nBundle\Registry\RedirectorRegistry;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

final class RedirectorAdapterPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $services = [];
        $definition = $container->getDefinition(RedirectorRegistry::class);
        $registry = $container->getParameter('i18n.registry');

        $redirectorRegistry = $registry['redirector'];

        foreach ($container->findTaggedServiceIds('i18n.adapter.redirector', true) as $serviceId => $attributes) {
            $priority = $attributes[0]['priority'] ?? 0;
            $alias = $attributes[0]['alias'] ?? null;
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
            $available = isset($redirectorRegistry[$serviceAlias]) ? $redirectorRegistry[$serviceAlias]['enabled'] : true;

            if (!$available) {
                continue;
            }

            $definition->addMethodCall('register', [$service['reference'], $serviceAlias]);

            $service['definition']->addMethodCall('setName', [$serviceAlias]);
            $service['definition']->addMethodCall('setConfig', [$redirectorRegistry[$serviceAlias]['config'] ?? []]);
        }
    }
}
