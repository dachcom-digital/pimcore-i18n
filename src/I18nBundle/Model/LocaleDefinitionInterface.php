<?php

namespace I18nBundle\Model;

interface LocaleDefinitionInterface
{
    public function getLocale(): ?string;

    public function hasLocale(): bool;

    public function getLanguageIso(): ?string;

    public function hasLanguageIso(): bool;

    public function getCountryIso(): ?string;

    public function hasCountryIso(): bool;
}
