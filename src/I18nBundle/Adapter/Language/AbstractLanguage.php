<?php

namespace I18nBundle\Adapter\Language;

use Pimcore\Config;

abstract class AbstractLanguage implements LanguageInterface
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
    protected $defaultLanguage = FALSE;

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
    public function getDefaultLanguage()
    {
        if ($this->defaultLanguage !== FALSE) {
            return $this->defaultLanguage;
        }

        $defaultCountry = NULL;
        $configDefaultLanguage = $this->currentZoneConfig['default_language'];

        if(!is_null($configDefaultLanguage)) {
            $defaultLanguage = $configDefaultLanguage;
        } else {
            $config = Config::getSystemConfig();
            $defaultLanguage = $config->general->defaultLanguage;
            if(strpos($defaultLanguage, '_') !== FALSE) {
                $defaultLanguage = array_shift(explode('_', $defaultLanguage));
            }
        }

        //set to NULL if empty since pimcore returns an empty string if no default language has been defined.
        $this->defaultLanguage = empty($defaultLanguage) ? NULL : $defaultLanguage;
        return $this->defaultLanguage;
    }
}