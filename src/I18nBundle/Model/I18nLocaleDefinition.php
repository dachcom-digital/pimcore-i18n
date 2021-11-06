<?php

namespace I18nBundle\Model;

class I18nLocaleDefinition implements I18nLocaleDefinitionInterface
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
