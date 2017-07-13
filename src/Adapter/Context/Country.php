<?php

namespace I18nBundle\Adapter\Context;

use I18nBundle\Tool\System;
use Pimcore\Model\Document;
use Pimcore\Cache;
use Pimcore\Model\Translation;
use Symfony\Component\Intl\Intl;

class Country extends AbstractContext
{
    /**
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
     * @param              $countryIso
     * @param string       $locale
     *
     * @return string|null
     */
    public function getCountryNameByIsoCode($countryIso, $locale = NULL)
    {
        if ($countryIso === 'GLOBAL') {
            return Translation\Website::getByKeyLocalized('International', TRUE, TRUE);
        }

        $countryName = Intl::getRegionBundle()->getCountryName($countryIso, $locale);

        if (!empty($countryName)) {
            return $countryName;
        }

        return NULL;
    }

    /**
     * @param bool $returnAsString
     *
     * @return string|array
     */
    public function getCurrentCountryAndLanguage($returnAsString = TRUE)
    {
        $currentCountryIso = $this->getCurrentCountryIso();

        if ($currentCountryIso === 'GLOBAL') {
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
     * @param boolean $force
     *
     * @return array|mixed
     */
    public function getActiveCountryLocalizations($force = FALSE)
    {
        $cacheKey = 'Website_ActiveCountryLocalizations' . $this->zoneManager->getCurrentZoneInfo('zoneId');
        $cachedData = Cache::load($cacheKey);
        $skipCache = \Pimcore\Tool::isFrontendRequestByAdmin() || $force === TRUE;

        if ($cachedData !== FALSE && $skipCache == FALSE) {
            return $cachedData;
        }

        $countryData = [];
        $activeCountries = $this->zoneManager->getCurrentZoneCountryAdapter()->getActiveCountries();
        $activeLanguages = $this->zoneManager->getCurrentZoneLanguageAdapter()->getActiveLanguages();
        $userLanguage = $this->userHelper->guessLanguage($activeLanguages);

        if (!empty($activeCountries)) {

            foreach ($activeCountries as $country) {

                if (is_null($country['isoCode']) | $country['isoCode'] === 'GLOBAL') {
                    continue;
                }

                $countryIso = $country['isoCode'];
                $languages = $this->getActiveLanguagesForCountry($countryIso);

                if ($languages === FALSE) {
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
            'languages'          => $this->getActiveLanguagesForCountry('GLOBAL'),
        ];

        if (!$skipCache) {
            Cache::save($countryData, $cacheKey, ['website', 'output']);
        }

        return $countryData;
    }

    public function getLinkedLanguages($onlyShowRootLanguages = FALSE)
    {
        $currentDocument = $this->getDocument();
        $urls = $this->pathGeneratorManager->getPathGenerator()->getUrls($currentDocument, $onlyShowRootLanguages);
        return $urls;
    }

    /**
     * Get Global Languages for Country.
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

        foreach ($tree as $domainElement) {
            if ($domainElement['countryIso'] === $countryIso) {
                $languages[] = $this->mapLanguageInfo($domainElement['languageIso'], $domainElement['countryIso'], $domainElement['url']);
            }
        }

        return $languages;
    }

}