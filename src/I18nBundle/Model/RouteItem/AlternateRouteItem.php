<?php

namespace I18nBundle\Model\RouteItem;

use I18nBundle\Model\ZoneSiteInterface;

class AlternateRouteItem extends BaseRouteItem implements AlternateRouteItemInterface
{
    protected ZoneSiteInterface $zoneSite;

    public function __construct(
        string $type,
        bool $headless,
        ZoneSiteInterface $zoneSite
    ) {
        parent::__construct($type, $headless);

        $this->zoneSite = $zoneSite;
    }

    public function getZoneSite(): ZoneSiteInterface
    {
        return $this->zoneSite;
    }

    public function isValidAlternateRoute(): bool
    {
        return $this->getEntity() !== null || !empty($this->getRouteName());
    }
}