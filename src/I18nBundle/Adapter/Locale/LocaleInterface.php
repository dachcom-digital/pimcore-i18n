<?php

namespace I18nBundle\Adapter\Locale;

interface LocaleInterface
{
    /**
     * @param int   $zoneId
     * @param array $zoneConfig
     */
    public function setCurrentZoneConfig($zoneId, $zoneConfig);

    /**
     * Get Active Locales.
     *
     * @return array
     */
    public function getActiveLocales(): array;

    /**
     * @param string $locale
     * @param string $field
     * @param string $keyIdentifier
     *
     * @return mixed
     */
    public function getLocaleData($locale, $field = null, $keyIdentifier = 'locale');

    /**
     * returns valid locale.
     *
     * @return bool|null|string
     */
    public function getDefaultLocale();

    /**
     * @return array
     */
    public function getGlobalInfo();
}
