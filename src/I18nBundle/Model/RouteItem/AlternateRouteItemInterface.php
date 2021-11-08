<?php

namespace I18nBundle\Model\RouteItem;

use I18nBundle\Model\ZoneSiteInterface;

interface AlternateRouteItemInterface extends BaseRouteItemInterface
{
    public function getZoneSite(): ZoneSiteInterface;

    public function isValidAlternateRoute(): bool;
}
