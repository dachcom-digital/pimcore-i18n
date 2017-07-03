<?php

namespace I18nBundle\ContextResolver\Context;

use I18nBundle\Tool\System;
use Pimcore\Model\Document;
use Pimcore\Cache;
use Symfony\Component\Intl\Intl;

class Country extends Language
{
    /**
     * Get valid Country Iso
     * @return bool|string
     */
    public function getCurrentCountryIso()
    {
        $isoCode = NULL;

        if (Cache\Runtime::isRegistered('i18n.countryIso')) {
            $isoCode = Cache\Runtime::get('i18n.countryIso');

            return $isoCode;
        }

        return FALSE;
    }

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
            $countryData = $this->configuration->getCountryAdapter()->getCountryData($countryIso, $field);
        }

        return $countryData;
    }

    /**
     * @param              $countryIso
     * @param string       $locale
     *
     * @return null
     */
    public function getCountryNameByIsoCode($countryIso, $locale = NULL)
    {
        $countryName = Intl::getRegionBundle()->getCountryName($countryIso, $locale);

        if (!empty($countryName)) {
            return $countryName;
        }

        return NULL;
    }

    /**
     * @param bool $returnAsString
     *
     * @return string
     */
    public function getCurrentCountryAndLanguage($returnAsString = TRUE)
    {
        $currentCountryIso = $this->getCurrentCountryIso();

        if (strtolower($currentCountryIso) === 'global') {
            $countryName = \Pimcore\Model\Translation\Website::getByKeyLocalized('International', TRUE, TRUE);
        } else {
            $countryName = Intl::getRegionBundle()->getCountryName($currentCountryIso, $this->getCurrentLanguage());
        }

        if ($returnAsString) {
            return $countryName . ' (' . $this->getCurrentLanguage() . ')';
        } else {
            return ['countryName' => $countryName, 'locale' => $this->getCurrentLanguage()];
        }
    }

    /**
     * @param boolean $force
     *
     * @return array|mixed
     */
    public function getActiveCountryLocalizations($force = FALSE)
    {
        $cacheKey = 'Website_ActiveCountryLocalizations';
        $cachedData = \Pimcore\Cache::load($cacheKey);
        $skipCache = \Pimcore\Tool::isFrontendRequestByAdmin() || $force === TRUE;

        if ($cachedData !== FALSE && $skipCache == FALSE) {
            //return $cachedData;
        }

        $activeCountries = $this->configuration->getCountryAdapter()->getActiveCountries();

        $countryData = [];

        $countryNames = Intl::getRegionBundle()->getCountryNames($this->getCurrentLanguage());

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

                $countryLocale = Intl::getRegionBundle()->getCountryName($countryIso, $this->guesser->guessLanguage());

                $countryData[$countryIsoLower] = [
                    'country'            => $country,
                    'countryTitleNative' => $countryLocale,
                    'countryTitle'       => $countryName,
                    'languages'          => $languages
                ];
            }
        }

        $countryData['global'] = [
            'country'   => $this->configuration->getCountryAdapter()->getGlobalInfo(),
            'languages' => $this->getActiveLanguagesForCountry('GLOBAL'),
        ];

        if (!$skipCache) {
            \Pimcore\Cache::save($countryData, $cacheKey, ['website', 'output']);
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
        $validLanguages = \Pimcore\Tool::getValidLanguages();

        if (is_null($countryIso)) {
            $countryIso = $this->getCurrentCountryIso();
        }

        $countryIsoLower = strtolower($countryIso);

        if ($countryIso === 'GLOBAL') {
            $globalPrefix = $this->configuration->getConfig('globalPrefix');
            $countrySlug = !empty($globalPrefix) ? '-' . $globalPrefix : '';
        } else {
            $countrySlug = !empty($countryIso) ? '-' . $countryIsoLower : '';
        }

        foreach ($validLanguages as $language) {
            $relatedDocument = Document::getByPath($this->relationHelper->getCurrentPageRootPath() . $language . $countrySlug);

            if (empty($relatedDocument) || !$relatedDocument->isPublished()) {
                continue;
            }

            $url = System::joinPath([\Pimcore\Tool::getHostUrl(), $relatedDocument->getKey()]);
            $languages[] = $this->mapLanguageInfo($language, $url);
        }

        return $languages;
    }

    /**
     * overrides the parent language getLinkedLanguage method
     *
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

            $validCountry = [];

            $countryIso = strtolower($this->getCurrentCountryIso());

            foreach ($data as $validCountryIso => $country) {
                //only current country
                if ($validCountryIso === $countryIso) {
                    $validCountry[$validCountryIso] = $country;
                    break;
                }
            }

            $urls = $this->getPathResolver()->getUrls($currentDocument, $validCountry);

            $validLinks = [];

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