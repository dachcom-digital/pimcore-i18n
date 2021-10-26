<?php

namespace I18nBundle\Model;

class I18nContext implements I18nContextInterface
{
    protected ?string $locale;
    protected ?string $languageIso;
    protected ?string $countryIso;
    protected bool $isValidZoneLocale;

    public function __construct(
        ?string $locale,
        ?string $languageIso,
        ?string $countryIso,
        bool $isValidZoneLocale,
    ) {
        $this->locale = $locale;
        $this->languageIso = $languageIso;
        $this->countryIso = $countryIso;
        $this->isValidZoneLocale = $isValidZoneLocale;
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

    public function isValidZoneLocale(): bool
    {
        return $this->isValidZoneLocale === true;
    }
}
