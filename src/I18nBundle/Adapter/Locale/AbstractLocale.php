<?php

namespace I18nBundle\Adapter\Locale;

abstract class AbstractLocale implements LocaleInterface
{
    /**
     * @var array|null
     */
    protected $currentZoneConfig = null;

    /**
     * @var int|null
     */
    protected $currentZoneId = null;

    /**
     * @param null|int $zoneId
     * @param array    $zoneConfig
     */
    public function setCurrentZoneConfig($zoneId, $zoneConfig)
    {
        $this->currentZoneId = $zoneId;
        $this->currentZoneConfig = $zoneConfig;
    }

}
