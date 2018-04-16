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
     * Get Active Locales
     *
     * @return array
     */
    function getActiveLocales(): array;

    /**
     * @param $locale
     * @param $field
     * @param $keyIdentifier
     *
     * @return mixed
     */
    function getLocaleData($locale, $field = null, $keyIdentifier = 'locale');

    /**
     * returns valid locale
     *
     * @return bool|mixed|null|string
     */
    public function getDefaultLocale();

}