<?php

namespace I18nBundle\Adapter\Context;

use Pimcore\Cache;
use Pimcore\Model\Translation;
use Symfony\Component\Intl\Intl;
use I18nBundle\Definitions;

class Country extends AbstractContext
{
    /**
     * Helper: Get current Country Iso.
     *
     * Get valid Country Iso
     *
     * @return bool|string
     */
    public function getCurrentCountryIso()
    {
        if (Cache\Runtime::isRegistered('i18n.countryIso')) {
            $isoCode = Cache\Runtime::get('i18n.countryIso');

            return $isoCode;
        }

        return false;
    }

    /**
     * Helper: Get current Country Info.
     *
     * @param string $field
     *
     * @return mixed|null
     *
     * @throws \Exception
     */
    public function getCurrentCountryInfo($field = 'name')
    {
        $countryData = null;

        if (Cache\Runtime::isRegistered('i18n.locale')) {
            $locale = Cache\Runtime::get('i18n.locale');
            $countryData = $this->zoneManager->getCurrentZoneLocaleAdapter()->getLocaleData($locale, $field);
        }

        return $countryData;
    }

    /**
     * Helper: Get Language Name By Iso Code.
     *
     * @param string $languageIso
     * @param string $locale
     * @param string $region
     *
     * @return string|null
     */
    public function getLanguageNameByIsoCode($languageIso, $locale = null, $region = null)
    {
        if ($languageIso === false) {
            return null;
        }

        $languageName = Intl::getLanguageBundle()->getLanguageName($languageIso, $region, $locale);

        if (!empty($languageName)) {
            return $languageName;
        }

        return null;
    }

    /**
     * Helper: Get Country Name by Iso Code.
     *
     * @param string $countryIso
     * @param string $locale
     *
     * @return string|null
     */
    public function getCountryNameByIsoCode($countryIso, $locale = null)
    {
        if ($countryIso === false) {
            return null;
        }

        if ($countryIso === Definitions::INTERNATIONAL_COUNTRY_NAMESPACE) {
            return Translation\Website::getByKeyLocalized('International', true, true);
        }

        $countryName = Intl::getRegionBundle()->getCountryName($countryIso, $locale);

        if (!empty($countryName)) {
            return $countryName;
        }

        return null;
    }

    /**
     * Helper: get current country and locale.
     *
     * @param bool $returnAsString
     *
     * @return string|array
     */
    public function getCurrentCountryAndLanguage($returnAsString = true)
    {
        $currentCountryIso = $this->getCurrentCountryIso();

        if ($currentCountryIso === Definitions::INTERNATIONAL_COUNTRY_NAMESPACE) {
            $countryName = Translation\Website::getByKeyLocalized('International', true, true);
        } else {
            $countryName = Intl::getRegionBundle()->getCountryName($currentCountryIso, $this->getCurrentLanguageIso());
        }

        if ($returnAsString === true) {
            return $countryName . ' (' . $this->getCurrentLanguageIso() . ')';
        } else {
            return ['countryName' => $countryName, 'locale' => $this->getCurrentLanguageIso()];
        }
    }

    /**
     * Helper: Get all active countries with all language related sites.
     *
     * @return array
     *
     * @throws \Exception
     */
    public function getActiveCountries()
    {
        $activeLocales = $this->zoneManager->getCurrentZoneLocaleAdapter()->getActiveLocales();

        $validCountries = [];
        foreach ($activeLocales as $id => $localeData) {
            $extendedCountryData = $localeData;

            // override the default locale isoCode,
            // we need the country iso code only
            $extendedCountryData['isoCode'] = null;

            if (strpos($localeData['locale'], '_') === false) {
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

                $countryTitleNative = Intl::getRegionBundle()->getCountryName($countryIso, $countryIso);
                $countryTitle = Intl::getRegionBundle()->getCountryName($countryIso, $this->getCurrentLanguageIso());

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
            'countryTitleNative' => Translation\Website::getByKeyLocalized('International', true, true, $this->getCurrentLocale()),
            'countryTitle'       => Translation\Website::getByKeyLocalized('International', true, true, $this->getCurrentLocale()),
            'languages'          => $this->getActiveLanguagesForCountry(Definitions::INTERNATIONAL_COUNTRY_NAMESPACE),
        ];

        return $countryData;
    }

    /**
     * Get languages for Country.
     * Only checks if root document in given country iso is accessible.
     *
     * @param null $countryIso
     *
     * @return array
     *
     * @throws \Exception
     */
    private function getActiveLanguagesForCountry($countryIso = null)
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
