<?php

namespace I18nBundle\Model;

use I18nBundle\Adapter\LocaleProvider\LocaleProviderInterface;

interface I18nZoneInterface
{
    public function getZoneId(): ?int;

    public function getZoneName(): ?string;

    public function getZoneDomains(): array;

    public function getMode(): string;

    public function getTranslations(): array;

    public function getContext(): I18nContextInterface;

    public function getLocaleProvider(): LocaleProviderInterface;

    /**
     * @return array<int, I18nSiteInterface>
     */
    public function getSites(bool $flatten = false): array;

    public function isActiveZone(): bool;

    public function getLocaleUrlMapping(): array;

    public function getCurrentSite(): I18nSiteInterface;

    public function getActiveLocaleInfo(string $field): mixed;

    public function getCurrentLocale(): ?string;

    public function getCurrentCountryAndLanguage(bool $returnAsString = true): string|array;

    public function getLinkedLanguages(bool $onlyShowRootLanguages = false): array;

    public function getActiveLanguages(): array;

    public function getActiveCountries(): array;

    public function getLanguageNameByIsoCode(string $languageIso, ?string $locale = null): ?string;

    public function getCountryNameByIsoCode(string $countryIso, ?string $locale = null): ?string;
}
