<?php

namespace I18nBundle\Model\RouteItem;

interface RouteItemInterface extends BaseRouteItemInterface
{
    public const STATIC_ROUTE = 'static_route';
    public const SYMFONY_ROUTE = 'symfony';
    public const DOCUMENT_ROUTE = 'document';
}
