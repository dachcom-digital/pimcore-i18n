<?php

namespace I18nBundle\Adapter\Country;

interface CountryInterface
{
    /**
     * @param $zoneId
     * @param $zoneConfig
     *
     * @return void
     */
    function setCurrentZoneConfig($zoneId, $zoneConfig);

    /**
     * Get Active Countries
     * @return array
     */
    function getActiveCountries(): array;

    /**
     * @return string
     */
    function getDefaultCountry();

    /**
     * @param $isoCode
     * @param $field
     *
     * @return mixed
     */
    function getCountryData($isoCode = '', $field = NULL);

    /**
     * get Info about global State
     * @return array
     */
    function getGlobalInfo();
}