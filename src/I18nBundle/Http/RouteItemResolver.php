<?php

namespace I18nBundle\Http;

use I18nBundle\Definitions;
use I18nBundle\Model\RouteItem\RouteItemInterface;
use Symfony\Component\HttpFoundation\Request;

class RouteItemResolver implements RouteItemResolverInterface
{
    public function setRouteItem(RouteItemInterface $i18nZone, Request $request): void
    {
        if ($this->hasRouteItem($request)) {
            throw new \Exception('I18n route item already has been resolved');
        }

        $request->attributes->set(Definitions::ATTRIBUTE_I18N_ROUTE_ITEM, $i18nZone);
    }

    public function getRouteItem(Request $request): ?RouteItemInterface
    {
        return $request->attributes->get(Definitions::ATTRIBUTE_I18N_ROUTE_ITEM);
    }

    public function hasRouteItem(Request $request): bool
    {
        $routeItem = $this->getRouteItem($request);

        return $routeItem instanceof RouteItemInterface;
    }
}
