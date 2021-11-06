<?php

namespace I18nBundle\Model\RouteItem;

use I18nBundle\Model\I18nZoneSiteInterface;

class AlternateRouteItem extends BaseRouteItem implements AlternateRouteItemInterface
{
    protected I18nZoneSiteInterface $zoneSite;

    public function __construct(
        string $type,
        bool $headless,
        I18nZoneSiteInterface $zoneSite
    ) {
        parent::__construct($type, $headless);

        $this->zoneSite = $zoneSite;
    }

    public function getZoneSite(): I18nZoneSiteInterface
    {
        return $this->zoneSite;
    }

    public function isValidAlternateRoute(): bool
    {
        return $this->getEntity() !== null || !empty($this->getRouteName());
    }
}