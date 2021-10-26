<?php

namespace I18nBundle\Model;

use I18nBundle\Adapter\LocaleProvider\LocaleProviderInterface;
use I18nBundle\Adapter\PathGenerator\PathGeneratorInterface;
use I18nBundle\Definitions;
use Pimcore\Model\Translation;
use Symfony\Component\Intl\Countries;
use Symfony\Component\Intl\Languages;

class I18nZone implements I18nZoneInterface
{
    protected ?int $zoneId;
    protected ?string $zoneName;
    protected array $zoneDomains;
    protected string $mode;
    protected array $translations;
    protected array $localeUrlMapping;
    protected I18nContextInterface $context;
    protected LocaleProviderInterface $localeProvider;
    protected PathGeneratorInterface $pathGenerator;
    protected array $sites;

    public function __construct(
        ?int $zoneId,
        ?string $zoneName,
        array $zoneDomains,
        string $mode,
        array $translations,
        I18nContextInterface $context,
        LocaleProviderInterface $localeProvider,
        PathGeneratorInterface $pathGenerator,
        array $sites
    ) {
        $this->zoneId = $zoneId;
        $this->zoneName = $zoneName;
        $this->zoneDomains = $zoneDomains;
        $this->mode = $mode;
        $this->translations = $translations;
        $this->context = $context;
        $this->localeProvider = $localeProvider;
        $this->pathGenerator = $pathGenerator;
        $this->sites = $sites;
        $this->localeUrlMapping = $this->buildLocaleUrlMappingForCurrentZone($sites);

        $this->pathGenerator->setZone($this);
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

    public function getTranslations(): array
    {
        return $this->translations;
    }

    public function getContext(): I18nContextInterface
    {
        return $this->context;
    }

    public function getLocaleProvider(): LocaleProviderInterface
    {
        return $this->localeProvider;
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

    public function getCurrentSite(): I18nSiteInterface
    {
        $sites = $this->getSites(true);
        $locale = $this->getContext()->getLocale();

        if (empty($locale)) {
            throw new \Exception('I18n: locale for current request not found.');
        }

        $treeIndex = array_search($locale, array_map(static function (I18nSiteInterface $site) {
            return $site->getLocale();
        }, $sites), true);

        if ($treeIndex === false) {
            throw new \Exception(sprintf('I18n: no valid site for locale "%s" found.', $locale));
        }

        return $sites[$treeIndex];
    }

    public function getActiveLocaleInfo(string $field): mixed
    {
        if (!$this->context->hasLocale()) {
            return null;
        }

        return $this->localeProvider->getLocaleData($this->context->getLocale(), $field);
    }

    public function getCurrentLocale(): ?string
    {
        if (!$this->context->hasLocale()) {
            return null;
        }

        try {
            $locale = $this->context->getLocale();
        } catch (\Exception $e) {
            return null;
        }

        return $locale;
    }

    public function getCurrentCountryAndLanguage(bool $returnAsString = true): string|array
    {
        $currentCountryIso = $this->context->getCountryIso();

        if ($currentCountryIso === Definitions::INTERNATIONAL_COUNTRY_NAMESPACE) {
            $countryName = Translation::getByKeyLocalized('International', 'messages', true, true);
        } else {
            $countryName = Countries::getName($currentCountryIso, $this->context->getLanguageIso());
        }

        if ($returnAsString === true) {
            return $countryName . ' (' . $this->context->getLanguageIso() . ')';
        }

        return [
            'countryName' => $countryName,
            'locale'      => $this->context->getLanguageIso()
        ];

    }

    public function getLinkedLanguages(bool $onlyShowRootLanguages = false): array
    {
        return $this->pathGenerator->getUrls($onlyShowRootLanguages);
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
            $languageData['active'] = $site->getLanguageIso() === $this->context->getLanguageIso();
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
        if ($this->mode !== 'country') {
            return [];
        }

        $activeLocales = $this->localeProvider->getActiveLocales();

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
                $countryTitle = Countries::getName($countryIso, $this->context->getLanguageIso());

                $countryData[] = [
                    'country'            => $country,
                    'countryTitleNative' => $countryTitleNative,
                    'countryTitle'       => $countryTitle,
                    'languages'          => $languages
                ];
            }
        }

        $countryData[] = [
            'country'            => $this->localeProvider->getGlobalInfo(),
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
            $countryIso = $this->context->getCountryIso();
        }

        $sites = $this->getSites(true);
        $linkedLanguages = $this->getLinkedLanguages();

        foreach ($sites as $site) {
            if ($site->getCountryIso() === $countryIso) {
                $languageData = $this->mapLanguageInfo($site->getLocale(), $site->getUrl());
                $languageData['linkedHref'] = $site->getUrl();
                $languageData['active'] = $site->getLanguageIso() === $this->context->getLanguageIso()
                    && $site->getCountryIso() === $this->context->getCountryIso();
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
            'title'       => Languages::getName($locale, $this->context->getLanguageIso()),
            'href'        => $href
        ];
    }

    protected function buildLocaleUrlMappingForCurrentZone(array $i18nSites = []): array
    {
        $localeUrlMapping = [];

        foreach ($this->flattenSites($i18nSites) as $i18nSite) {
            if (!empty($i18nSite->getLocale())) {
                $localeUrlMapping[$i18nSite->getLocale()] = $i18nSite->getLocaleUrlMapping();
            }
        }

        return $localeUrlMapping;
    }

    /**
     * @return array<int, I18nSiteInterface>
     */
    protected function flattenSites(array $i18nSites): array
    {
        $elements = [];
        /** @var I18nSiteInterface $i18nSite */
        foreach ($i18nSites as $i18nSite) {

            if (!empty($i18nSite->getCountryIso()) || !empty($i18nSite->getLanguageIso())) {
                $elements[] = $i18nSite;
            }

            if ($i18nSite->hasSubSites()) {
                foreach ($i18nSite->getSubSites() as $subSite) {
                    $elements[] = $subSite;
                }
            }

            $elements[] = $i18nSite;
        }

        return $elements;
    }

}
