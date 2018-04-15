<?php

namespace I18nBundle\Adapter\Locale;

use I18nBundle\Definitions;
use Pimcore\Config;
use Pimcore\Tool;

class System implements LocaleInterface
{
    /**
     * @var null
     */
    protected $validCountries = null;

    /**
     * @var null
     */
    protected $validLanguages = null;

    /**
     * @var array|null
     */
    protected $currentZoneConfig = null;

    /**
     * @var int|null
     */
    protected $currentZoneId = null;

    /**
     * @var bool|null|string
     */
    protected $defaultLocale = false;

    /**
     * @param null|int $zoneId
     * @param array    $zoneConfig
     */
    public function setCurrentZoneConfig($zoneId, $zoneConfig)
    {
        $this->currentZoneId = $zoneId;
        $this->currentZoneConfig = $zoneConfig;
    }

    /**
     * returns valid locale
     *
     * @return bool|mixed|null|string
     */
    public function getDefaultLocale()
    {
        if ($this->defaultLocale !== false) {
            return $this->defaultLocale;
        }

        $defaultLocale = null;
        $configDefaultLocale = $this->currentZoneConfig['default_locale'];

        if (!is_null($configDefaultLocale)) {
            $defaultLocale = $configDefaultLocale;
        } else {
            $config = Config::getSystemConfig();
            $defaultSystemLocale = $config->general->defaultLanguage;
            $defaultLocale = $defaultSystemLocale;
        }

        $this->defaultLocale = $defaultLocale;

        return $this->defaultLocale;
    }

    /**
     * @return array
     */
    public function getActiveLanguages(): array
    {
        if (!empty($this->validLanguages)) {
            return $this->validLanguages;
        }

        $validLanguages = [];
        $languages = Tool::getValidLanguages();

        //unset($languages[1]);

        foreach ($languages as $id => $language) {
            $validLanguages[] = [
                'id'      => (int)$id,
                'isoCode' => $language
            ];
        }

        $this->validLanguages = $validLanguages;

        return $this->validLanguages;
    }

    /**
     * @param string $isoCode
     * @param null   $field
     *
     * @return mixed
     */
    public function getLanguageData($isoCode = '', $field = null)
    {
        $key = array_search($isoCode, array_column($this->validLanguages, 'isoCode'));
        if ($key !== false) {
            return $this->validLanguages[$key][$field];
        }

        return null;
    }

    /**
     * @return array
     */
    public function getActiveCountries(): array
    {
        if (!empty($this->validCountries)) {
            return $this->validCountries;
        }

        $validCountries = [$this->getGlobalInfo()];
        foreach (Tool::getValidLanguages() as $id => $language) {

            if (strpos($language, '_') === false) {
                continue;
            }

            $parts = explode('_', $language);
            $isoCode = $parts[1];

            //skip country if it's already in the list.
            if (array_search($isoCode, array_column($validCountries, 'isoCode')) !== false) {
                continue;
            }

            $validCountries[] = [
                'isoCode' => $isoCode,
                'id'      => $id,
                'zone'    => null,
                'object'  => null
            ];
        }

        $this->validCountries = $validCountries;

        return $this->validCountries;
    }

    /**
     * @param string $isoCode
     * @param null   $field
     *
     * @return null
     */
    public function getCountryData($isoCode = '', $field = null)
    {
        $key = array_search($isoCode, array_column($this->validCountries, 'isoCode'));
        if ($key !== false) {
            return $this->validCountries[$key][$field];
        }

        return null;
    }

    /**
     * @return array
     */
    public function getGlobalInfo()
    {
        return [
            'isoCode' => Definitions::INTERNATIONAL_COUNTRY_NAMESPACE,
            'id'      => null,
            'zone'    => null,
            'object'  => null
        ];
    }
}