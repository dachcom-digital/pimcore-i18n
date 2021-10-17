<?php

namespace I18nBundle\Adapter\Locale;

abstract class AbstractLocale implements LocaleInterface
{
    protected ?array $currentZoneConfig = null;
    protected ?int $currentZoneId = null;

    public function setCurrentZoneConfig(?int $zoneId, ?array $zoneConfig): void
    {
        $this->currentZoneId = $zoneId;
        $this->currentZoneConfig = $zoneConfig;
    }
}
