<?php

namespace I18nBundle\Model\RouteItem;

use I18nBundle\Model\I18nLocaleDefinitionInterface;
use I18nBundle\Model\I18nZoneInterface;

interface RouteItemInterface extends BaseRouteItemInterface
{
    public const STATIC_ROUTE = 'static_route';
    public const SYMFONY_ROUTE = 'symfony';
    public const DOCUMENT_ROUTE = 'document';

    public function setI18nZone(I18nZoneInterface $i18nZone): void;

    public function getI18nZone(): ?I18nZoneInterface;

    public function hasI18nZone(): bool;

    public function setLocaleDefinition(I18nLocaleDefinitionInterface $i18nLocaleDefinition);

    public function getLocaleDefinition(): I18nLocaleDefinitionInterface;
}
