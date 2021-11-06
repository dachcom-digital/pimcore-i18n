<?php

namespace I18nBundle\Model\RouteItem;

use I18nBundle\Model\I18nLocaleDefinitionInterface;
use I18nBundle\Model\I18nZoneInterface;

class RouteItem extends BaseRouteItem implements RouteItemInterface
{
    protected I18nLocaleDefinitionInterface $i18nLocaleDefinition;
    protected ?I18nZoneInterface $i18nZone = null;

    public function __construct(string $type, bool $headless)
    {
        parent::__construct($type, $headless);
    }

    public function setI18nZone(I18nZoneInterface $i18nZone): void
    {
        $this->i18nZone = $i18nZone;
    }

    public function getI18nZone(): ?I18nZoneInterface
    {
        return $this->i18nZone;
    }

    public function hasI18nZone(): bool
    {
        return $this->i18nZone instanceof I18nZoneInterface;
    }

    public function setLocaleDefinition(I18nLocaleDefinitionInterface $i18nLocaleDefinition)
    {
        $this->i18nLocaleDefinition = $i18nLocaleDefinition;
    }

    public function getLocaleDefinition(): I18nLocaleDefinitionInterface
    {
        return $this->i18nLocaleDefinition;
    }
}
