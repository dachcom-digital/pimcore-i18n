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

    public function zoneHasSwitched()
    {
        return $this->params['zoneHasSwitched'];
    }

    public function zoneSwitchedFrom()
    {
        return $this->params['zoneFrom'];
    }

    public function zoneSwitchedTo()
    {
        return $this->params['zoneTo'];
    }

    public function languageHasSwitched()
    {
        return $this->params['languageHasSwitched'];
    }

    public function languageSwitchedFrom()
    {
        return $this->params['languageFrom'];
    }

    public function languageSwitchedTo()
    {
        return $this->params['languageTo'];
    }

    public function countryHasSwitched()
    {
        return $this->params['countryHasSwitched'];
    }

    public function countrySwitchedFrom()
    {
        return $this->params['countryFrom'];
    }

    public function countrySwitchedTo()
    {
        return $this->params['countryTo'];
    }
}