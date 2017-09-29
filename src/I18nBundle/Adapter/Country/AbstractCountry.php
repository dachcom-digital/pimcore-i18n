<?php

namespace I18nBundle\Adapter\Country;

use Pimcore\Config;

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
     * @var bool|null|string
     */
    protected $defaultCountry = FALSE;

    /**
     * @param null|int $zoneId
     * @param array $zoneConfig
     */
    public function setCurrentZoneConfig($zoneId, $zoneConfig)
    {
        $this->currentZoneId = $zoneId;
        $this->currentZoneConfig = $zoneConfig;
    }

    /**
     * @return string|null
     */
    public function getDefaultCountry()
    {
        if ($this->defaultCountry !== FALSE) {
            return $this->defaultCountry;
        }

        $defaultCountry = NULL;
        $configDefaultCountry = $this->currentZoneConfig['default_country'];

        if(!is_null($configDefaultCountry)) {
            $defaultCountry = $configDefaultCountry;
        } else {
            $config = Config::getSystemConfig();
            $defaultLanguage = $config->general->defaultLanguage;
            if (strpos($defaultLanguage, '_') === FALSE) {
                $defaultCountry = $this->getGlobalInfo()['isoCode'];
            } else {
                $defaultCountry = end(explode('_', $defaultLanguage));
            }
        }

        $this->defaultCountry = $defaultCountry;

        return $this->defaultCountry;
    }
}