<?php

namespace I18nBundle\Adapter\Locale;

interface LocaleInterface
{
    /**
     * @param $zoneId
     * @param $zoneConfig
     *
     * @return void
     */
    function setCurrentZoneConfig($zoneId, $zoneConfig);

    /**
     * Get Active Languages
     *
     * @return array
     */
    function getActiveLanguages(): array;

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
    function getLanguageData($isoCode = '', $field = null);

    /**
     * @param $isoCode
     * @param $field
     *
     * @return mixed
     */
    function getCountryData($isoCode = '', $field = NULL);

    /**
     * returns valid locale
     *
     * @return bool|mixed|null|string
     */
    public function getDefaultLocale();

}