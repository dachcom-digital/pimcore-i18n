<?php

namespace I18nBundle\Adapter\Context;

use Pimcore\Cache;
use Pimcore\Model\Translation;
use Symfony\Component\Intl\Countries;
use Symfony\Component\Intl\Intl;
use I18nBundle\Definitions;
use Symfony\Component\Intl\Languages;

class Country extends AbstractContext
{
    public function getCurrentCountryIso(): ?string
    {
        if (Cache\Runtime::isRegistered('i18n.countryIso')) {
            return Cache\Runtime::get('i18n.countryIso');
        }

        return null;
    }

    public function getCurrentCountryInfo(string $field = 'name'): mixed
    {
        $countryData = null;

        if (Cache\Runtime::isRegistered('i18n.locale')) {
            $locale = Cache\Runtime::get('i18n.locale');
            $countryData = $this->zoneManager->getCurrentZoneLocaleAdapter()->getLocaleData($locale, $field);
        }

        return $countryData;
    }

    public function getLanguageNameByIsoCode(?string $languageIso, ?string $locale = null, ?string $region = null): ?string
    {
        if (empty($languageIso)) {
            return null;
        }

        $languageName = Languages::getName($languageIso, $locale);

        if (!empty($languageName)) {
            return $languageName;
        }

        return null;
    }

    public function getCountryNameByIsoCode(?string $countryIso, ?string $locale = null): ?string
    {
        if (empty($countryIso)) {
            return null;
        }

        if ($countryIso === Definitions::INTERNATIONAL_COUNTRY_NAMESPACE) {
            return Translation::getByKeyLocalized('International', 'messages', true, true);
        }

        $countryName = Countries::getName($countryIso, $locale);

        if (!empty($countryName)) {
            return $countryName;
        }

        return null;
    }

    public function getCurrentCountryAndLanguage(bool $returnAsString = true): string|array
    {
        $currentCountryIso = $this->getCurrentCountryIso();

        if ($currentCountryIso === Definitions::INTERNATIONAL_COUNTRY_NAMESPACE) {
            $countryName = Translation::getByKeyLocalized('International', 'messages', true, true);
        } else {
            $countryName = Countries::getName($currentCountryIso, $this->getCurrentLanguageIso());
        }

        if ($returnAsString === true) {
            return $countryName . ' (' . $this->getCurrentLanguageIso() . ')';
        }

        return ['countryName' => $countryName, 'locale' => $this->getCurrentLanguageIso()];

    }

    public function getActiveCountries() :array
    {
        $activeLocales = $this->zoneManager->getCurrentZoneLocaleAdapter()->getActiveLocales();

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

            //skip country if it's already in the list.
            if (array_search($isoCode, array_column($validCountries, 'isoCode')) !== false) {
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
                $countryTitle = Countries::getName($countryIso, $this->getCurrentLanguageIso());

                $countryData[] = [
                    'country'            => $country,
                    'countryTitleNative' => $countryTitleNative,
                    'countryTitle'       => $countryTitle,
                    'languages'          => $languages
                ];
            }
        }

        $countryData[] = [
            'country'            => $this->zoneManager->getCurrentZoneLocaleAdapter()->getGlobalInfo(),
            'countryTitleNative' => Translation::getByKeyLocalized('International', 'messages', true, true, $this->getCurrentLocale()),
            'countryTitle'       => Translation::getByKeyLocalized('International', 'messages', true, true, $this->getCurrentLocale()),
            'languages'          => $this->getActiveLanguagesForCountry(Definitions::INTERNATIONAL_COUNTRY_NAMESPACE),
        ];

        return $countryData;
    }

    /**
     * Get languages for Country.
     * Only checks if root document in given country iso is accessible.
     */
    private function getActiveLanguagesForCountry(?string $countryIso = null): array
    {
        $languages = [];

        if (is_null($countryIso)) {
            $countryIso = $this->getCurrentCountryIso();
        }

        $tree = $this->zoneManager->getCurrentZoneDomains(true);
        $linkedLanguages = $this->getLinkedLanguages();

        foreach ($tree as $domainElement) {
            if ($domainElement['countryIso'] === $countryIso) {
                $languageData = $this->mapLanguageInfo($domainElement['locale'], $domainElement['url']);
                $languageData['linkedHref'] = $domainElement['url'];
                $languageData['active'] = $domainElement['languageIso'] === $this->getCurrentLanguageIso()
                    && $domainElement['countryIso'] === $this->getCurrentCountryIso();
                foreach ($linkedLanguages as $linkedLanguage) {
                    if ($linkedLanguage['languageIso'] === $domainElement['languageIso'] && $countryIso === $linkedLanguage['countryIso']) {
                        $languageData['linkedHref'] = $linkedLanguage['url'];

                        break;
                    }
                }

                $languages[] = $languageData;
            }
        }

        return $languages;
    }
}
