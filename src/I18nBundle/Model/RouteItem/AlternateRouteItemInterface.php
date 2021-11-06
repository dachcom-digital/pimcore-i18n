<?php

namespace I18nBundle\Model\RouteItem;

use I18nBundle\Model\I18nZoneSiteInterface;

interface AlternateRouteItemInterface extends BaseRouteItemInterface
{
    public function getZoneSite(): I18nZoneSiteInterface;

    public function isValidAlternateRoute(): bool;
}
