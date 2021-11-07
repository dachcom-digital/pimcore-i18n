<?php

namespace I18nBundle\Context;

use I18nBundle\Model\LocaleDefinitionInterface;
use I18nBundle\Model\RouteItem\RouteItemInterface;
use I18nBundle\Model\ZoneInterface;
use I18nBundle\Model\ZoneSiteInterface;

interface I18nContextInterface
{
    public function getRouteItem(): RouteItemInterface;

    public function getZone(): ZoneInterface;

    public function getLocaleDefinition(): LocaleDefinitionInterface;

    public function getCurrentZoneSite(): ZoneSiteInterface;

    public function getCurrentLocale(): ?string;

    public function getCurrentLocaleInfo(string $field): mixed;

    public function getLocaleInfo(string $locale, string $field): mixed;

    public function getZoneDefaultLocale(): ?string;

    public function getZoneActiveLocales(): ?array;

    public function getZoneGlobalInfo(): array;

    public function getCurrentCountryAndLanguage(bool $returnAsString = true): string|array;

    public function getLinkedLanguages(bool $onlyShowRootLanguages = false): array;

    public function getActiveLanguages(): array;

    public function getActiveCountries(): array;

    public function getLanguageNameByIsoCode(string $languageIso, ?string $locale = null): ?string;

    public function getCountryNameByIsoCode(string $countryIso, ?string $locale = null): ?string;
}
