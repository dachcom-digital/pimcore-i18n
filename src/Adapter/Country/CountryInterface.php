<?php

namespace I18nBundle\Adapter\Country;

interface CountryInterface
{
    /**
     * @param $zoneId
     *
     * @return void
     */
    function setCurrentZoneId($zoneId);

    /**
     * Get Active Countries
     * @return array
     */
    function getActiveCountries(): array;

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