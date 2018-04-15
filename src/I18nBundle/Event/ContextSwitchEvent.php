<?php

namespace I18nBundle\Event;

use Symfony\Component\EventDispatcher\Event;

class ContextSwitchEvent extends Event
{
    /**
     * @var
     */
    private $params = [];

    /**
     * ContextSwitchEvent constructor.
     *
     * @param $params
     */
    public function __construct($params)
    {
        $this->params = $params;
    }

    /**
     * @return bool
     */
    public function zoneHasSwitched()
    {
        return $this->params['zoneHasSwitched'];
    }

    /**
     * @return string|null
     */
    public function zoneSwitchedFrom()
    {
        return $this->params['zoneFrom'];
    }

    /**
     * @return string|null
     */
    public function zoneSwitchedTo()
    {
        return $this->params['zoneTo'];
    }

    /**
     * @return bool
     */
    public function localeHasSwitched()
    {
        return $this->params['localeHasSwitched'];
    }

    /**
     * @return string|null
     */
    public function localeSwitchedFrom()
    {
        return $this->params['localeFrom'];
    }

    /**
     * @return string|null
     */
    public function localeSwitchedTo()
    {
        return $this->params['localeTo'];
    }

    /**
     * @return bool
     */
    public function languageHasSwitched()
    {
        return $this->params['languageHasSwitched'];
    }

    /**
     * @return string|null
     */
    public function languageSwitchedFrom()
    {
        return $this->params['languageFrom'];
    }

    /**
     * @return string|null
     */
    public function languageSwitchedTo()
    {
        return $this->params['languageTo'];
    }

    /**
     * @return bool
     */
    public function countryHasSwitched()
    {
        return $this->params['countryHasSwitched'];
    }

    /**
     * @return string|null
     */
    public function countrySwitchedFrom()
    {
        return $this->params['countryFrom'];
    }

    /**
     * @return string|null
     */
    public function countrySwitchedTo()
    {
        return $this->params['countryTo'];
    }
}