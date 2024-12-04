<?php

/*
 * This source file is available under two different licenses:
 *   - GNU General Public License version 3 (GPLv3)
 *   - DACHCOM Commercial License (DCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) DACHCOM.DIGITAL AG (https://www.dachcom-digital.com)
 * @license    GPLv3 and DCL
 */

namespace I18nBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;

class ContextSwitchEvent extends Event
{
    private array $params;

    public function __construct(array $params)
    {
        $this->params = $params;
    }

    public function zoneHasSwitched(): bool
    {
        return $this->params['zoneHasSwitched'];
    }

    public function zoneSwitchedFrom(): ?string
    {
        return $this->params['zoneFrom'];
    }

    public function zoneSwitchedTo(): ?string
    {
        return $this->params['zoneTo'];
    }

    public function localeHasSwitched(): bool
    {
        return $this->params['localeHasSwitched'];
    }

    public function localeSwitchedFrom(): ?string
    {
        return $this->params['localeFrom'];
    }

    public function localeSwitchedTo(): ?string
    {
        return $this->params['localeTo'];
    }

    public function languageHasSwitched(): ?string
    {
        return $this->params['languageHasSwitched'];
    }

    public function languageSwitchedFrom(): ?string
    {
        return $this->params['languageFrom'];
    }

    public function languageSwitchedTo(): ?string
    {
        return $this->params['languageTo'];
    }

    public function countryHasSwitched(): bool
    {
        return $this->params['countryHasSwitched'];
    }

    public function countrySwitchedFrom(): ?string
    {
        return $this->params['countryFrom'];
    }

    public function countrySwitchedTo(): ?string
    {
        return $this->params['countryTo'];
    }
}
