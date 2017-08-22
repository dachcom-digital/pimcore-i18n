<?php

namespace I18nBundle\Adapter\Country;

abstract class AbstractCountry implements CountryInterface
{
    /**
     * @var null|int
     */
    protected $currentZoneId = NULL;

    /**
     * @param $zoneId
     */
    public function setCurrentZoneId($zoneId)
    {
        $this->currentZoneId = $zoneId;
    }
}