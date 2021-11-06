<?php

namespace I18nBundle\Model;

use I18nBundle\Adapter\LocaleProvider\LocaleProviderInterface;
use I18nBundle\Adapter\PathGenerator\PathGeneratorInterface;
use I18nBundle\Definitions;
use I18nBundle\Model\RouteItem\RouteItemInterface;
use Pimcore\Model\Translation;
use Symfony\Component\Intl\Countries;
use Symfony\Component\Intl\Languages;

class I18nZone implements I18nZoneInterface
{
    protected ?int $zoneId;
    protected ?string $zoneName;
    protected string $mode;
    protected array $zoneDomains;
    protected array $zoneDefinition;
    protected array $localeUrlMapping;
    protected RouteItemInterface $routeItem;
    protected LocaleProviderInterface $localeProvider;
    protected PathGeneratorInterface $pathGenerator;
    protected array $sites;

    public function __construct(
        ?int $zoneId,
        ?string $zoneName,
        string $mode,
        array $zoneDomains,
        array $zoneDefinition,
        RouteItemInterface $routeItem,
        LocaleProviderInterface $localeProvider,
        PathGeneratorInterface $pathGenerator,
        array $sites
    ) {
        $this->zoneId = $zoneId;
        $this->zoneName = $zoneName;
        $this->mode = $mode;
        $this->zoneDomains = $zoneDomains;
        $this->zoneDefinition = $zoneDefinition;
        $this->routeItem = $routeItem;
        $this->localeProvider = $localeProvider;
        $this->pathGenerator = $pathGenerator;
        $this->sites = $sites;
        $this->localeUrlMapping = $this->buildLocaleUrlMappingForCurrentZone($sites);
    }

    public function getZoneId(): ?int
    {
        return $this->zoneId;
    }

    public function getZoneName(): ?string
    {
        return $this->zoneName;
    }

    public function getZoneDomains(): array
    {
        return $this->zoneDomains;
    }

    public function getMode(): string
    {
        return $this->mode;
    }

    public function getRouteItem(): RouteItemInterface
    {
        return $this->routeItem;
    }

    public function getTranslations(): array
    {
        return $this->zoneDefinition['translations'];
    }

    public function getSites(bool $flatten = false): array
    {
        return $flatten ? $this->flattenSites($this->sites) : $this->sites;
    }

    public function isActiveZone(): bool
    {
        return $this->zoneId !== null;
    }

    public function getLocaleUrlMapping(): array
    {
        return $this->localeUrlMapping;
    }

    public function getCurrentSite(): I18nZoneSiteInterface
    {
        $sites = $this->getSites(true);
        $locale = $this->routeItem->getLocaleDefinition()->getLocale();

        if (empty($locale)) {
            throw new \Exception('I18n: locale for current request not found.');
        }

        $treeIndex = array_search($locale, array_map(static function (I18nZoneSiteInterface $site) {
            return $site->getLocale();
        }, $sites), true);

        if ($treeIndex === false) {
            throw new \Exception(sprintf('I18n: no valid site for locale "%s" found.', $locale));
        }

        return $sites[$treeIndex];
    }

    public function getCurrentLocale(): ?string
    {
        if (!$this->routeItem->getLocaleDefinition()->hasLocale()) {
            return null;
        }

        try {
            $locale = $this->routeItem->getLocaleDefinition()->getLocale();
        } catch (\Exception $e) {
            return null;
        }

        return $locale;
    }

    public function getCurrentLocaleInfo(string $field): mixed
    {
        if (!$this->routeItem->getLocaleDefinition()->hasLocale()) {
            return null;
        }

        return $this->localeProvider->getLocaleData($this->zoneDefinition, $this->routeItem->getLocaleDefinition()->getLocale(), $field);
    }

    public function getLocaleProviderLocaleInfo(string $locale, string $field): mixed
    {
        return $this->localeProvider->getLocaleData($this->zoneDefinition, $locale, $field);
    }

    public function getLocaleProviderDefaultLocale(): ?string
    {
        return $this->localeProvider->getDefaultLocale($this->zoneDefinition);
    }

    public function getLocaleProviderActiveLocales(): ?array
    {
        return $this->localeProvider->getActiveLocales($this->zoneDefinition);
    }

    public function getLocaleProviderGlobalInfo(): array
    {
        return $this->localeProvider->getGlobalInfo($this->zoneDefinition);
    }

    public function getCurrentCountryAndLanguage(bool $returnAsString = true): string|array
    {
        $currentCountryIso = $this->routeItem->getLocaleDefinition()->getCountryIso();

        if ($currentCountryIso === Definitions::INTERNATIONAL_COUNTRY_NAMESPACE) {
            $countryName = Translation::getByKeyLocalized('International', 'messages', true, true);
        } else {
            $countryName = Countries::getName($currentCountryIso, $this->routeItem->getLocaleDefinition()->getLanguageIso());
        }

        if ($returnAsString === true) {
            return $countryName . ' (' . $this->routeItem->getLocaleDefinition()->getLanguageIso() . ')';
        }

        return [
            'countryName' => $countryName,
            'locale'      => $this->routeItem->getLocaleDefinition()->getLanguageIso()
        ];

    }

    public function getLinkedLanguages(bool $onlyShowRootLanguages = false): array
    {
        return $this->pathGenerator->getUrls($this, $onlyShowRootLanguages);
    }

    public function getActiveLanguages(): array
    {
        $languages = [];
        $sites = $this->getSites(true);
        $linkedLanguages = $this->getLinkedLanguages();

        foreach ($sites as $site) {

            if (empty($site->getLanguageIso())) {
                continue;
            }

            $languageData = $this->mapLanguageInfo($site->getLocale(), $site->getUrl());
            $languageData['linkedHref'] = $site->getUrl();
            $languageData['active'] = $site->getLanguageIso() === $this->routeItem->getLocaleDefinition()->getLanguageIso();
            foreach ($linkedLanguages as $linkedLanguage) {
                if ($linkedLanguage['languageIso'] === $site->getLanguageIso()) {
                    $languageData['linkedHref'] = $site->getUrl();

                    break;
                }
            }

            $languages[] = $languageData;
        }

        return $languages;
    }

    public function getActiveCountries(): array
    {
        if ($this->getMode() !== 'country') {
            return [];
        }

        $activeLocales = $this->localeProvider->getActiveLocales($this->zoneDefinition);

        $validCountries = [];
        foreach ($activeLocales as $id => $localeData) {
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
                $countryTitle = Countries::getName($countryIso, $this->routeItem->getLocaleDefinition()->getLanguageIso());

                $countryData[] = [
                    'country'            => $country,
                    'countryTitleNative' => $countryTitleNative,
                    'countryTitle'       => $countryTitle,
                    'languages'          => $languages
                ];
            }
        }

        $countryData[] = [
            'country'            => $this->localeProvider->getGlobalInfo($this->zoneDefinition),
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

    /**
     * Get languages for Country.
     * Only checks if root document in given country iso is accessible.
     */
    protected function getActiveLanguagesForCountry(?string $countryIso = null): array
    {
        $languages = [];

        if (is_null($countryIso)) {
            $countryIso = $this->routeItem->getLocaleDefinition()->getCountryIso();
        }

        $sites = $this->getSites(true);
        $linkedLanguages = $this->getLinkedLanguages();

        foreach ($sites as $site) {
            if ($site->getCountryIso() === $countryIso) {
                $languageData = $this->mapLanguageInfo($site->getLocale(), $site->getUrl());
                $languageData['linkedHref'] = $site->getUrl();
                $languageData['active'] = $site->getLanguageIso() === $this->routeItem->getLocaleDefinition()->getLanguageIso()
                    && $site->getCountryIso() === $this->routeItem->getLocaleDefinition()->getCountryIso();
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
            'title'       => Languages::getName($locale, $this->routeItem->getLocaleDefinition()->getLanguageIso()),
            'href'        => $href
        ];
    }

    protected function buildLocaleUrlMappingForCurrentZone(array $i18nZoneSites = []): array
    {
        $localeUrlMapping = [];

        foreach ($this->flattenSites($i18nZoneSites) as $i18nZoneSite) {
            if (!empty($i18nZoneSite->getLocale())) {
                $localeUrlMapping[$i18nZoneSite->getLocale()] = $i18nZoneSite->getLocaleUrlMapping();
            }
        }

        return $localeUrlMapping;
    }

    /**
     * @return array<int, I18nZoneSiteInterface>
     */
    protected function flattenSites(array $i18nZoneSites): array
    {
        $elements = [];
        /** @var I18nZoneSiteInterface $i18nZoneSite */
        foreach ($i18nZoneSites as $i18nZoneSite) {

            if (!empty($i18nZoneSite->getCountryIso()) || !empty($i18nZoneSite->getLanguageIso())) {
                $elements[] = $i18nZoneSite;
            }

            if ($i18nZoneSite->hasSubSites()) {
                foreach ($i18nZoneSite->getSubSites() as $subSite) {
                    $elements[] = $subSite;
                }
            }

            $elements[] = $i18nZoneSite;
        }

        return $elements;
    }
}
