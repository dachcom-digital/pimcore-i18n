<?php

namespace I18nBundle\Adapter\Language;

interface LanguageInterface
{
    /**
     * @param $zoneId
     *
     * @return void
     */
    function setCurrentZoneId($zoneId);

    /**
     * Get Active Languages
     * @return array
     */
    function getActiveLanguages(): array;

    /**
     * @param $isoCode
     * @param $field
     *
     * @return mixed
     */
    function getLanguageData($isoCode = '', $field = NULL);

    /**
     * @return string
     */
    function getDefaultLanguage(): string;
}