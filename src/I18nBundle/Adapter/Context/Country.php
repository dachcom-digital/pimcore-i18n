<?php

namespace I18nBundle\Adapter\Context;

use Pimcore\Cache;
use Pimcore\Model\Translation;
use Symfony\Component\Intl\Intl;
use I18nBundle\Definitions;

class Country extends AbstractContext
{
    /**
     * Helper: Get current Country Info
     *
     * @param $field
     *
     * @return string
     */
    public function getCurrentCountryInfo($field = 'name')
    {
        $countryData = NULL;

        if (Cache\Runtime::isRegistered('i18n.countryIso')) {
            $countryIso = Cache\Runtime::get('i18n.countryIso');
            $countryData = $this->zoneManager->getCurrentZoneCountryAdapter()->getCountryData($countryIso, $field);
        }

        return $countryData;
    }

    /**
     * Helper: Get Country Name by Iso Code
     *
     * @param              $countryIso
     * @param string       $locale
     *
     * @return string|null
     */
    public function getCountryNameByIsoCode($countryIso, $locale = NULL)
    {
        if ($countryIso === FALSE) {
            return NULL;
        }

        if ($countryIso === Definitions::INTERNATIONAL_COUNTRY_NAMESPACE) {
            return Translation\Website::getByKeyLocalized('International', TRUE, TRUE);
        }

        $countryName = Intl::getRegionBundle()->getCountryName($countryIso, $locale);

        if (!empty($countryName)) {
            return $countryName;
        }

        return NULL;
    }

    /**
     * Helper: get current country and locale.
     *
     * @param bool $returnAsString
     *
     * @return string|array
     */
    public function getCurrentCountryAndLanguage($returnAsString = TRUE)
    {
        $currentCountryIso = $this->getCurrentCountryIso();

        if ($currentCountryIso === Definitions::INTERNATIONAL_COUNTRY_NAMESPACE) {
            $countryName = Translation\Website::getByKeyLocalized('International', TRUE, TRUE);
        } else {
            $countryName = Intl::getRegionBundle()->getCountryName($currentCountryIso, $this->getCurrentLanguageIso());
        }

        if ($returnAsString === TRUE) {
            return $countryName . ' (' . $this->getCurrentLanguageIso() . ')';
        } else {
            return ['countryName' => $countryName, 'locale' => $this->getCurrentLanguageIso()];
        }
    }

    /**
     * Helper: Get all active countries with all language related sites
     *
     * @return array|mixed
     */
    public function getActiveCountries()
    {
        $countryData = [];
        $activeCountries = $this->zoneManager->getCurrentZoneCountryAdapter()->getActiveCountries();
        $activeLanguages = $this->zoneManager->getCurrentZoneLanguageAdapter()->getActiveLanguages();
        $userLanguage = $this->userHelper->guessLanguage($activeLanguages);

        if (!empty($activeCountries)) {
            foreach ($activeCountries as $country) {

                if (is_null($country['isoCode']) | $country['isoCode'] === Definitions::INTERNATIONAL_COUNTRY_NAMESPACE) {
                    continue;
                }

                $countryIso = $country['isoCode'];
                $languages = $this->getActiveLanguagesForCountry($countryIso);

                if (empty($languages)) {
                    continue;
                }

                $countryTitleNative = Intl::getRegionBundle()->getCountryName($countryIso, $userLanguage);
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
            'country'            => $this->zoneManager->getCurrentZoneCountryAdapter()->getGlobalInfo(),
            'countryTitleNative' => Translation\Website::getByKeyLocalized('International', TRUE, TRUE, $userLanguage),
            'countryTitle'       => Translation\Website::getByKeyLocalized('International', TRUE, TRUE),
            'languages'          => $this->getActiveLanguagesForCountry(Definitions::INTERNATIONAL_COUNTRY_NAMESPACE),
        ];

        return $countryData;
    }

    /**
     * @deprecated This method is deprecated and will be removed in i18n 2.2. Use getActiveCountries() instead!
     *
     * @return array|mixed
     */
    public function getActiveCountryLocalizations()
    {
        return $this->getActiveCountries();
    }

    /**
     * Get languages for Country.
     * Only checks if root document in given country iso is accessible.
     *
     * @param null $countryIso
     *
     * @return array|bool
     */
    private function getActiveLanguagesForCountry($countryIso = NULL)
    {
        $languages = [];

        if (is_null($countryIso)) {
            $countryIso = $this->getCurrentCountryIso();
        }

        $tree = $this->zoneManager->getCurrentZoneDomains(TRUE);
        $linkedLanguages = $this->getLinkedLanguages();

        foreach ($tree as $domainElement) {
            if ($domainElement['countryIso'] === $countryIso) {
                $languageData = $this->mapLanguageInfo($domainElement['languageIso'], $domainElement['countryIso'], $domainElement['url']);
                $languageData['linkedHref'] = $domainElement['url'];
                foreach($linkedLanguages as $linkedLanguage) {
                    if($linkedLanguage['languageIso'] === $domainElement['languageIso'] && $countryIso === $linkedLanguage['countryIso']) {
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