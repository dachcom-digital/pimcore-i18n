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

namespace I18nBundle\Context;

use I18nBundle\Adapter\PathGenerator\PathGeneratorInterface;
use I18nBundle\Definitions;
use I18nBundle\Exception\ZoneSiteNotFoundException;
use I18nBundle\Model\LocaleDefinitionInterface;
use I18nBundle\Model\RouteItem\RouteItemInterface;
use I18nBundle\Model\ZoneInterface;
use I18nBundle\Model\ZoneSiteInterface;
use Pimcore\Model\Translation;
use Symfony\Component\Intl\Countries;
use Symfony\Component\Intl\Languages;

class I18nContext implements I18nContextInterface
{
    protected RouteItemInterface $routeItem;
    protected ZoneInterface $zone;
    protected LocaleDefinitionInterface $localeDefinition;
    protected ?PathGeneratorInterface $pathGenerator;
    protected ?ZoneSiteInterface $currentZoneSite = null;

    /**
     * @throws ZoneSiteNotFoundException
     */
    public function __construct(
        RouteItemInterface $routeItem,
        ZoneInterface $zone,
        LocaleDefinitionInterface $localeDefinition,
        ?PathGeneratorInterface $pathGenerator,
    ) {
        $this->routeItem = $routeItem;
        $this->zone = $zone;
        $this->pathGenerator = $pathGenerator;
        $this->localeDefinition = $localeDefinition;
        $this->currentZoneSite = $this->determinateCurrentZoneSite();
    }

    public function getRouteItem(): RouteItemInterface
    {
        return $this->routeItem;
    }

    public function getZone(): ZoneInterface
    {
        return $this->zone;
    }

    public function getLocaleDefinition(): LocaleDefinitionInterface
    {
        return $this->localeDefinition;
    }

    public function getCurrentZoneSite(): ZoneSiteInterface
    {
        return $this->currentZoneSite;
    }

    public function getCurrentLocale(): ?string
    {
        if (!$this->localeDefinition->hasLocale()) {
            return null;
        }

        try {
            $locale = $this->localeDefinition->getLocale();
        } catch (\Exception $e) {
            return null;
        }

        return $locale;
    }

    public function getCurrentLocaleInfo(string $field): mixed
    {
        if (!$this->localeDefinition->hasLocale()) {
            return null;
        }

        return $this->getLocaleData($this->localeDefinition->getLocale(), $field);
    }

    public function getLocaleInfo(string $locale, string $field): mixed
    {
        return $this->getLocaleData($locale, $field);
    }

    public function getZoneDefaultLocale(): ?string
    {
        return $this->zone->getDefaultLocale();
    }

    public function getZoneActiveLocales(): ?array
    {
        return $this->zone->getActiveLocales();
    }

    public function getZoneGlobalInfo(): array
    {
        return $this->zone->getGlobalInfo();
    }

    public function getCurrentCountryAndLanguage(bool $returnAsString = true): string|array
    {
        $currentCountryIso = $this->localeDefinition->getCountryIso();

        if ($currentCountryIso === Definitions::INTERNATIONAL_COUNTRY_NAMESPACE) {
            $countryName = Translation::getByKeyLocalized('International', 'messages', true, true);
        } else {
            $countryName = Countries::getName($currentCountryIso, $this->localeDefinition->getLanguageIso());
        }

        if ($returnAsString === true) {
            return $countryName . ' (' . $this->localeDefinition->getLanguageIso() . ')';
        }

        return [
            'countryName' => $countryName,
            'locale'      => $this->localeDefinition->getLanguageIso()
        ];
    }

    public function getLinkedLanguages(bool $onlyShowRootLanguages = false): array
    {
        if (!$this->pathGenerator instanceof PathGeneratorInterface) {
            throw new \Exception('This I18n context has a non booted path generator');
        }

        return $this->pathGenerator->getUrls($this, $onlyShowRootLanguages);
    }

    public function getActiveLanguages(): array
    {
        $languages = [];
        $sites = $this->zone->getSites(true);
        $linkedLanguages = $this->getLinkedLanguages();

        foreach ($sites as $site) {
            if (empty($site->getLanguageIso())) {
                continue;
            }

            $languageData = $this->mapLanguageInfo($site->getLocale(), $site->getUrl());
            $languageData['linkedHref'] = $site->getUrl();
            $languageData['active'] = $site->getLocale() === $this->localeDefinition->getLocale();

            foreach ($linkedLanguages as $linkedLanguage) {
                if ($linkedLanguage['locale'] === $site->getLocale()) {
                    $languageData['linkedHref'] = $linkedLanguage['url'];

                    break;
                }
            }

            $languages[] = $languageData;
        }

        return $languages;
    }

    public function getActiveCountries(): array
    {
        $activeLocales = $this->zone->getActiveLocales();

        $validCountries = [];
        foreach ($activeLocales as $localeData) {
            $extendedCountryData = $localeData;

            // override the default locale isoCode,
            // we need the country iso code only
            $extendedCountryData['isoCode'] = null;

            if (!str_contains($localeData['locale'], '_')) {
                continue;
            }

            $parts = explode('_', $localeData['locale']);
            $isoCode = strtoupper($parts[1]);

            $extendedCountryData['isoCode'] = $isoCode;

            // skip country if it's already in the list
            if (in_array($isoCode, array_column($validCountries, 'isoCode'), true)) {
                continue;
            }

            $validCountries[] = $extendedCountryData;
        }

        $countryData = [];
        if (!empty($validCountries)) {
            foreach ($validCountries as $country) {
                if (is_null($country['isoCode'])) {
                    continue;
                }

                $countryIso = $country['isoCode'];
                $languages = $this->getActiveLanguagesForCountry($countryIso);

                if (empty($languages)) {
                    continue;
                }

                $countryTitleNative = Countries::getName($countryIso, $countryIso);
                $countryTitle = Countries::getName($countryIso, $this->localeDefinition->getLanguageIso());

                $countryData[] = [
                    'country'            => $country,
                    'countryTitleNative' => $countryTitleNative,
                    'countryTitle'       => $countryTitle,
                    'languages'          => $languages
                ];
            }
        }

        $countryData[] = [
            'country'            => $this->zone->getGlobalInfo(),
            'countryTitleNative' => Translation::getByKeyLocalized('International', 'messages', true, true, $this->getCurrentLocale()),
            'countryTitle'       => Translation::getByKeyLocalized('International', 'messages', true, true, $this->getCurrentLocale()),
            'languages'          => $this->getActiveLanguagesForCountry(Definitions::INTERNATIONAL_COUNTRY_NAMESPACE),
        ];

        return $countryData;
    }

    public function getLanguageNameByIsoCode(string $languageIso, ?string $locale = null): ?string
    {
        $languageName = Languages::getName($languageIso, $locale);

        if (!empty($languageName)) {
            return $languageName;
        }

        return null;
    }

    public function getCountryNameByIsoCode(string $countryIso, ?string $locale = null): ?string
    {
        if ($countryIso === Definitions::INTERNATIONAL_COUNTRY_NAMESPACE) {
            return Translation::getByKeyLocalized('International', 'messages', true, true);
        }

        $countryName = Countries::getName($countryIso, $locale);

        if (!empty($countryName)) {
            return $countryName;
        }

        return null;
    }

    protected function getLocaleData(string $locale, string $field, string $keyIdentifier = 'locale'): mixed
    {
        $activeLocales = $this->zone->getActiveLocales();

        $index = array_search($locale, array_column($activeLocales, $keyIdentifier), true);

        if ($index !== false) {
            return $activeLocales[$index][$field];
        }

        return null;
    }

    protected function determinateCurrentZoneSite(): ?ZoneSiteInterface
    {
        $sites = $this->zone->getSites(true);
        $locale = $this->localeDefinition->getLocale();
        $zoneIdentifier = $this->zone->getId() ?? 0;

        if (empty($locale)) {
            return null;
        }

        $activeSites = array_values(array_filter($sites, static function (ZoneSiteInterface $site) {
            return $site->isActive() === true;
        }));

        if (count($activeSites) === 0) {
            throw new ZoneSiteNotFoundException(sprintf(
                'No zone site for locale "%s" found. Available zone (Id %d) site locales: %s',
                $locale,
                $zoneIdentifier,
                implode(', ', array_map(static function (ZoneSiteInterface $site) {
                    return $site->getLocale();
                }, $sites))
            ));
        }

        if (count($activeSites) > 1) {
            throw new ZoneSiteNotFoundException(sprintf(
                'Ambiguous locale definition for zone (Id %d) sites detected ("%s" was requested, multiple paths [%s] matched).',
                $zoneIdentifier,
                $locale,
                implode(', ', array_map(static function (ZoneSiteInterface $site) {
                    return $site->getFullPath();
                }, $activeSites))
            ));
        }

        return $activeSites[0];
    }

    /**
     * Get languages for Country.
     * Only checks if root document in given country iso is accessible.
     */
    protected function getActiveLanguagesForCountry(?string $countryIso = null): array
    {
        $languages = [];

        if (is_null($countryIso)) {
            $countryIso = $this->localeDefinition->getCountryIso();
        }

        $sites = $this->zone->getSites(true);
        $linkedLanguages = $this->getLinkedLanguages();

        foreach ($sites as $site) {
            if ($site->getCountryIso() === $countryIso) {
                $languageData = $this->mapLanguageInfo($site->getLocale(), $site->getUrl());
                $languageData['linkedHref'] = $site->getUrl();
                $languageData['active'] = $site->getLanguageIso() === $this->localeDefinition->getLanguageIso()
                    && $site->getCountryIso() === $this->localeDefinition->getCountryIso();
                foreach ($linkedLanguages as $linkedLanguage) {
                    if ($linkedLanguage['languageIso'] === $site->getLanguageIso() && $countryIso === $linkedLanguage['countryIso']) {
                        $languageData['linkedHref'] = $linkedLanguage['url'];

                        break;
                    }
                }

                $languages[] = $languageData;
            }
        }

        return $languages;
    }

    protected function mapLanguageInfo(string $locale, string $href): array
    {
        $iso = explode('_', $locale);

        return [
            'iso'         => $iso[0],
            'titleNative' => Languages::getName($locale, $iso[0]),
            'title'       => Languages::getName($locale, $this->localeDefinition->getLanguageIso()),
            'href'        => $href
        ];
    }
}
