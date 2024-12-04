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

namespace I18nBundle\Transformer;

use I18nBundle\Model\RouteItem\LinkGeneratorRouteItem;
use I18nBundle\Model\RouteItem\LinkGeneratorRouteItemInterface;
use I18nBundle\Model\RouteItem\RouteItem;
use I18nBundle\Model\RouteItem\RouteItemInterface;

class LinkGeneratorRouteItemTransformer implements TransformerInterface
{
    public function transform(RouteItemInterface $routeItem, array $context = []): LinkGeneratorRouteItemInterface
    {
        $staticRouteName = $context['staticRouteName'] ?? null;

        $linkGeneratorRouteItem = new LinkGeneratorRouteItem($staticRouteName, $routeItem->isHeadless());
        $linkGeneratorRouteItem->getRouteAttributesBag()->add($routeItem->getRouteAttributes());
        $linkGeneratorRouteItem->getRouteParametersBag()->add($routeItem->getRouteParameters());
        $linkGeneratorRouteItem->getRouteContextBag()->add($routeItem->getRouteContext());

        return $linkGeneratorRouteItem;
    }

    public function reverseTransform(mixed $transformedRouteItem, array $context = []): RouteItemInterface
    {
        if (!$transformedRouteItem instanceof LinkGeneratorRouteItemInterface) {
            throw new \Exception(
                sprintf(
                    'Transformed route item must be instance of "%s", "%s" given.',
                    LinkGeneratorRouteItemInterface::class,
                    get_class($transformedRouteItem)
                )
            );
        }

        $routeItem = new RouteItem($transformedRouteItem->getType(), $transformedRouteItem->isHeadless());
        $routeItem->setRouteName($transformedRouteItem->getRouteName());
        $routeItem->getRouteAttributesBag()->add($transformedRouteItem->getRouteAttributes());
        $routeItem->getRouteParametersBag()->add($transformedRouteItem->getRouteParameters());
        $routeItem->getRouteContextBag()->add($transformedRouteItem->getRouteContext());

        return $routeItem;
    }

    public function reverseTransformToArray(mixed $transformedRouteItem, array $context = []): array
    {
        throw new \Exception('Not Supported');
    }
}
