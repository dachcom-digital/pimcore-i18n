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

use I18nBundle\Builder\RouteItemBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

final class RouterPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        /* @phpstan-ignore-next-line */
        if ($container->has('router.default')) {
            $container->getDefinition(RouteItemBuilder::class)->addMethodCall('setFrameworkRouter', [new Reference('router.default')]);
        }
    }
}
