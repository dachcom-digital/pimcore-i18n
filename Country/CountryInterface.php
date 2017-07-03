<?php

namespace I18nBundle\Country;

interface CountryInterface
{
    /**
     * Get Active Countries
     * @return array
     */
    function getActiveCountries();

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