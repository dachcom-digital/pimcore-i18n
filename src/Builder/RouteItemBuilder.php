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

namespace I18nBundle\Builder;

use I18nBundle\Definitions;
use I18nBundle\Exception\RouteItemException;
use I18nBundle\Factory\RouteItemFactory;
use I18nBundle\Model\RouteItem\RouteItemInterface;
use I18nBundle\Modifier\RouteItem\RouteItemModifier;
use Pimcore\Model\Document;
use Symfony\Bundle\FrameworkBundle\Routing\Router as FrameworkRouter;
use Symfony\Component\HttpFoundation\Request;

class RouteItemBuilder
{
    protected ?FrameworkRouter $frameworkRouter = null;
    protected RouteItemFactory $routeItemFactory;
    protected RouteItemModifier $routeItemModifier;

    public function __construct(
        RouteItemFactory $routeItemFactory,
        RouteItemModifier $routeItemModifier
    ) {
        $this->routeItemFactory = $routeItemFactory;
        $this->routeItemModifier = $routeItemModifier;
    }

    public function setFrameworkRouter(FrameworkRouter $router): void
    {
        $this->frameworkRouter = $router;
    }

    public function buildRouteItemByParameters(string $type, array $i18nRouteParameters): RouteItemInterface
    {
        $routeItem = $this->routeItemFactory->createFromArray($type, true, $i18nRouteParameters);

        $this->routeItemModifier->modifyByParameters(
            $routeItem->getType(),
            $routeItem,
            $i18nRouteParameters,
            [
                'router' => $this->frameworkRouter
            ]
        );

        if (!$routeItem->hasLocaleFragment()) {
            throw new RouteItemException(
                sprintf(
                    'Cannot build route item for type "%s" because locale fragment is missing',
                    $routeItem->getType()
                )
            );
        }

        if (!$routeItem->getRouteContextBag()->has('isFrontendRequestByAdmin')) {
            $routeItem->getRouteContextBag()->set('isFrontendRequestByAdmin', false);
        }

        return $routeItem;
    }

    /**
     * @throws RouteItemException
     */
    public function buildRouteItemByRequest(Request $baseRequest, ?Document $baseDocument): ?RouteItemInterface
    {
        $pimcoreRequestSource = $baseRequest->attributes->get('pimcore_request_source');
        $currentRouteName = $baseRequest->attributes->get('_route');

        $routeItem = null;
        if ($pimcoreRequestSource === 'staticroute') {
            $routeItem = $this->routeItemFactory->create(RouteItemInterface::STATIC_ROUTE, false);
        } elseif (str_starts_with($currentRouteName, 'document_')) {
            $routeItem = $this->routeItemFactory->create(RouteItemInterface::DOCUMENT_ROUTE, false);
        } elseif ($baseRequest->attributes->has(Definitions::ATTRIBUTE_I18N_ROUTE_IDENTIFIER)) {
            $routeItem = $this->routeItemFactory->create(RouteItemInterface::SYMFONY_ROUTE, false);
        }

        if ($routeItem === null) {
            return null;
        }

        $routeItem->setRouteName($currentRouteName);

        $this->routeItemModifier->modifyByRequest(
            $routeItem->getType(),
            $routeItem,
            $baseRequest,
            [
                'document' => $baseDocument,
                'router'   => $this->frameworkRouter
            ]
        );

        if (!$routeItem->hasLocaleFragment()) {
            throw new RouteItemException(
                sprintf(
                    'Cannot build route item for type "%s" because locale fragment is missing',
                    $routeItem->getType()
                )
            );
        }

        return $routeItem;
    }
}
