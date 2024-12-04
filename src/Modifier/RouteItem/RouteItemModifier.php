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

namespace I18nBundle\Modifier\RouteItem;

use I18nBundle\Model\RouteItem\RouteItemInterface;
use I18nBundle\Modifier\RouteItem\Type\RouteItemModifierInterface;
use Symfony\Component\HttpFoundation\Request;

class RouteItemModifier
{
    protected iterable $modifier;

    public function __construct(iterable $modifier = [])
    {
        $this->modifier = $modifier;
    }

    public function modifyByParameters(string $type, RouteItemInterface $routeItem, array $parameters, array $context = []): RouteItemInterface
    {
        /** @var RouteItemModifierInterface $modifier */
        foreach ($this->modifier as $modifier) {
            if ($modifier->supportParameters($type, $routeItem, $parameters, $context)) {
                $modifier->modifyByParameters($routeItem, $parameters, $context);
            }
        }

        return $routeItem;
    }

    public function modifyByRequest(string $type, RouteItemInterface $routeItem, Request $request, array $context = []): RouteItemInterface
    {
        /** @var RouteItemModifierInterface $modifier */
        foreach ($this->modifier as $modifier) {
            if ($modifier->supportRequest($type, $routeItem, $request, $context)) {
                $modifier->modifyByRequest($routeItem, $request, $context);
            }
        }

        return $routeItem;
    }
}
