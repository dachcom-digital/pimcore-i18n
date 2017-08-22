<?php

namespace I18nBundle\Adapter\Language;

abstract class AbstractLanguage implements LanguageInterface
{
    /**
     * @var null|int
     */
    protected $currentZoneId = NULL;

    /**
     * @param $zoneId
     */
    public function setCurrentZoneId($zoneId)
    {
        $this->currentZoneId = $zoneId;
    }
}