<?php

namespace I18nBundle\Model;

interface I18nContextInterface
{
    public function getLocale(): ?string;

    public function hasLocale(): bool;

    public function getLanguageIso(): ?string;

    public function hasLanguageIso(): bool;

    public function getCountryIso(): ?string;

    public function hasCountryIso(): bool;

    public function isValidZoneLocale(): bool;
}
