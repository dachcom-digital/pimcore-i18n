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

namespace I18nBundle\Model;

class LocaleDefinition implements LocaleDefinitionInterface
{
    protected ?string $locale;
    protected ?string $languageIso;
    protected ?string $countryIso;

    public function __construct(
        ?string $locale,
        ?string $languageIso,
        ?string $countryIso,
    ) {
        $this->locale = $locale;
        $this->languageIso = $languageIso;
        $this->countryIso = $countryIso;
    }

    public function getLocale(): ?string
    {
        return $this->locale;
    }

    public function hasLocale(): bool
    {
        return $this->locale !== null;
    }

    public function getLanguageIso(): ?string
    {
        return $this->languageIso;
    }

    public function hasLanguageIso(): bool
    {
        return $this->languageIso !== null;
    }

    public function getCountryIso(): ?string
    {
        return $this->countryIso;
    }

    public function hasCountryIso(): bool
    {
        return $this->countryIso !== null;
    }
}
