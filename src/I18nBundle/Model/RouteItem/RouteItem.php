<?php

namespace I18nBundle\Model\RouteItem;

class RouteItem extends BaseRouteItem implements RouteItemInterface
{
    public function __construct(string $type, bool $headless)
    {
        parent::__construct($type, $headless);
    }
}
