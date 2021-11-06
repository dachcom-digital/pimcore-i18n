<?php

namespace I18nBundle\Model;

use I18nBundle\Model\RouteItem\RouteItemInterface;

interface I18nZoneInterface
{
    public function getZoneId(): ?int;

    public function getZoneName(): ?string;

    public function getZoneDomains(): array;

    public function getMode(): string;

    public function getRouteItem(): RouteItemInterface;

    public function getTranslations(): array;

    /**
     * @return array<int, I18nZoneSiteInterface>
     */
    public function getSites(bool $flatten = false): array;

    public function isActiveZone(): bool;

    public function getLocaleUrlMapping(): array;

    public function getCurrentSite(): I18nZoneSiteInterface;

    public function getCurrentLocale(): ?string;

    public function getCurrentLocaleInfo(string $field): mixed;

    public function getLocaleProviderLocaleInfo(string $locale, string $field): mixed;

    public function getLocaleProviderDefaultLocale(): ?string;

    public function getLocaleProviderActiveLocales(): ?array;

    public function getLocaleProviderGlobalInfo(): array;

    public function getCurrentCountryAndLanguage(bool $returnAsString = true): string|array;

    public function getLinkedLanguages(bool $onlyShowRootLanguages = false): array;

    public function getActiveLanguages(): array;

    public function getActiveCountries(): array;

    public function getLanguageNameByIsoCode(string $languageIso, ?string $locale = null): ?string;

    public function getCountryNameByIsoCode(string $countryIso, ?string $locale = null): ?string;
}
