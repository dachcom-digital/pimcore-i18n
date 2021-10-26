<?php

namespace I18nBundle\Adapter\LocaleProvider;

abstract class AbstractLocaleProvider implements LocaleProviderInterface
{
    protected ?array $currentZoneConfig = null;
    protected ?int $currentZoneId = null;

    public function setCurrentZoneConfig(?int $zoneId, ?array $zoneConfig): void
    {
        $this->currentZoneId = $zoneId;
        $this->currentZoneConfig = $zoneConfig;
    }
}
