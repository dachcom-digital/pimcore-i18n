<?php

namespace I18nBundle\Adapter\Language;

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
     * @param null|int $zoneId
     * @param array $zoneConfig
     */
    public function setCurrentZoneConfig($zoneId, $zoneConfig)
    {
        $this->currentZoneId = $zoneId;
        $this->currentZoneConfig = $zoneConfig;
    }
}