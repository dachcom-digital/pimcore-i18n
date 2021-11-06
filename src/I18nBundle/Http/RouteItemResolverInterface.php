<?php

namespace I18nBundle\Http;

use I18nBundle\Model\RouteItem\RouteItemInterface;
use Symfony\Component\HttpFoundation\Request;

interface RouteItemResolverInterface
{
    public function setRouteItem(RouteItemInterface $i18nZone, Request $request);

    public function getRouteItem(Request $request): ?RouteItemInterface;

    public function hasRouteItem(Request $request): bool;
}
