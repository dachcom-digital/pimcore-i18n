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
           // return $cachedData;
        }

        $countryData = [];
        $activeCountries = $this->zoneManager->getCurrentZoneCountryAdapter()->getActiveCountries();
        $countryNames = Intl::getRegionBundle()->getCountryNames($this->getCurrentLanguageIso());

        if (!empty($activeCountries)) {
            foreach ($activeCountries as $country) {
                if (is_null($country['isoCode'])) {
                    continue;
                }

                $countryIso = $country['isoCode'];
                $countryIsoLower = strtolower($countryIso);
                $languages = $this->getActiveLanguagesForCountry($countryIso);

                if ($languages === FALSE) {
                    continue;
                }

                $countryName = '';

                foreach ($countryNames as $countryNameIso => $_countryName) {
                    if ($countryNameIso === $countryIso) {
                        $countryName = $_countryName;
                        break;
                    }
                }

                $validLanguages = $this->zoneManager->getCurrentZoneLanguageAdapter()->getValidLanguages();
                $countryLocale = Intl::getRegionBundle()->getCountryName($countryIso, $this->userHelper->guessLanguage($validLanguages));

                $countryData[$countryIsoLower] = [
                    'country'            => $country,
                    'countryTitleNative' => $countryLocale,
                    'countryTitle'       => $countryName,
                    'languages'          => $languages
                ];
            }
        }

        $countryData['global'] = [
            'country'   => $this->zoneManager->getCurrentZoneCountryAdapter()->getGlobalInfo(),
            'languages' => $this->getActiveLanguagesForCountry('GLOBAL'),
        ];

        if (!$skipCache) {
            Cache::save($countryData, $cacheKey, ['website', 'output']);
        }

        return $countryData;
    }

    /**
     * Get Global Languages for Country.
     * Only checks if root document in given country iso is accessible.
     *
     * @param null $countryIso
     *
     * @return array|bool
     */
    public function getActiveLanguagesForCountry($countryIso = NULL)
    {
        $languages = [];
        $validLanguages = $this->zoneManager->getCurrentZoneLanguageAdapter()->getValidLanguages();

        if (is_null($countryIso)) {
            $countryIso = $this->getCurrentCountryIso();
        }

        $countryIsoLower = strtolower($countryIso);

        if ($countryIso === 'GLOBAL') {
            $globalPrefix = $this->zoneManager->getCurrentZoneInfo('global_prefix');
            $countrySlug = !empty($globalPrefix) ? '-' . $globalPrefix : '';
        } else {
            $countrySlug = !empty($countryIso) ? '-' . $countryIsoLower : '';
        }

        foreach ($validLanguages as $language) {
            $relatedDocument = Document::getByPath($this->documentHelper->getCurrentPageRootPath() . $language['isoCode'] . $countrySlug);

            if (empty($relatedDocument) || !$relatedDocument->isPublished()) {
                continue;
            }

            $url = System::joinPath([\Pimcore\Tool::getHostUrl(), $relatedDocument->getKey()]);
            $languages[] = $this->mapLanguageInfo($language['isoCode'], $url);
        }

        return $languages;
    }

    /**
     * @param bool $onlyShowRootLanguages
     * @param bool $strictMode if false and document couldn't be found, the country root page will be shown
     *                         Mostly used for navigation drop downs or lists.
     *                         Get all linked documents from given document in current country!
     *
     * @return array|bool|mixed
     */
    public function getLinkedLanguages($onlyShowRootLanguages = TRUE, $strictMode = FALSE)
    {
        $activeLanguagesForCountry = $this->getActiveLanguagesForCountry();

        if ($onlyShowRootLanguages === TRUE) {
            return $activeLanguagesForCountry;
        } else {
            $currentDocument = $this->getDocument();
            $data = $this->getActiveCountryLocalizations();

            $validCountries = [];

            $countryIso = strtolower($this->getCurrentCountryIso());

            foreach ($data as $validCountryIso => $country) {
                //only current country
                if ($validCountryIso === $countryIso) {
                    $validCountries[$validCountryIso] = $country;
                    break;
                }
            }

            $validLinks = [];
            $urls = $this->pathGeneratorManager->getPathGenerator()->getUrls($currentDocument, $validCountries);

            foreach ($urls as $url) {
                $validLinks[] = $this->mapLanguageInfo($url['language'], $url['href']);
            }

            //add missing languages, if strictMode is off.
            if ($strictMode === FALSE) {
                $compareArray = array_diff(
                    array_column($activeLanguagesForCountry, 'iso'),
                    array_column($validLinks, 'iso')
                );

                foreach ($activeLanguagesForCountry as $languageInfo) {
                    if (in_array($languageInfo['iso'], $compareArray)) {
                        $validLinks[] = $languageInfo;
                    }
                }
            }

            return $validLinks;
        }
    }
}