<?php

namespace I18nBundle\Adapter\Country;

abstract class AbstractCountry implements CountryInterface
{
    /**
     * @var array|null
     */
    protected $currentZoneConfig = NULL;

    /**
     * @var int|null
     */
    protected $currentZoneId = NULL;

    /**
     * @param null|int $zoneId
     * @param array $zoneConfig
     */
    public function setCurrentZoneConfig($zoneId, $zoneConfig)
    {
        $this->currentZoneId = $zoneId;
        $this->currentZoneConfig = $zoneConfig;
    }
}