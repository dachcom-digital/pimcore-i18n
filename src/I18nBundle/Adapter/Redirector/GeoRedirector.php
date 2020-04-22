<?php

namespace I18nBundle\Adapter\Redirector;

use I18nBundle\Helper\UserHelper;
use I18nBundle\Manager\ZoneManager;

class GeoRedirector extends AbstractRedirector
{
    /**
     * @var ZoneManager
     */
    protected $zoneManager;

    /**
     * @var UserHelper
     */
    protected $userHelper;

    /**
     * @param ZoneManager $zoneManager
     * @param UserHelper  $userHelper
     */
    public function __construct(
        ZoneManager $zoneManager,
        UserHelper $userHelper
    ) {
        $this->zoneManager = $zoneManager;
        $this->userHelper = $userHelper;
    }

    /**
     * @param RedirectorBag $redirectorBag
     */
    public function makeDecision(RedirectorBag $redirectorBag)
    {
        if ($this->lastRedirectorWasSuccessful($redirectorBag) === true) {
            return;
        }

        /**
         * - Based on string source HTTP_ACCEPT_LANGUAGE ("de,en;q=0.9,de-DE;q=0.8;...")
         * - Transformed by symfony to [de, en, de_DE, ...]
         */
        $userLanguagesIso = $this->userHelper->getLanguagesAcceptedByUser();

        if (!is_array($userLanguagesIso) || count($userLanguagesIso) === 0) {
            $this->setDecision([
                'valid'             => false,
                'redirectorOptions' => [
                    'geoLanguage' => false,
                    'geoCountry'  => false,
                ]
            ]);

            return;
        }

        $userCountryIso = null;
        if ($redirectorBag->getI18nMode() === 'country') {
            $userCountryIso = $this->userHelper->guessCountry();
        }

        $redirectorOptions = [
            'geoLanguage' => $userLanguagesIso,
            'geoCountry'  => $userCountryIso !== null ? $userCountryIso : false,
        ];

        $prioritisedListQuery = [];
        $prioritisedList = [
            ['ignoreCountry' => false, 'countryStrictMode' => true, 'languageStrictMode' => false],
            ['ignoreCountry' => false, 'countryStrictMode' => false, 'languageStrictMode' => false],
            ['ignoreCountry' => true, 'countryStrictMode' => false, 'languageStrictMode' => true]
        ];

        foreach ($prioritisedList as $index => $list) {
            foreach ($userLanguagesIso as $priority => $userLocale) {

                $country = $list['ignoreCountry'] ? null : $userCountryIso;
                $countryStrictMode = $list['countryStrictMode'];
                $languageStrictMode = $list['languageStrictMode'];

                if (null !== $zoneData = $this->findUrlInZoneTree($userLocale, $country, $countryStrictMode, $languageStrictMode)) {
                    $prioritisedListQuery[] = [
                        'priority' => $index === 0 ? -1 : $priority,
                        'data'     => $zoneData
                    ];
                    break;
                }
            }
        }

        // nothing found.
        if (count($prioritisedListQuery) === 0) {
            $this->setDecision(['valid' => false, 'redirectorOptions' => $redirectorOptions]);
            return;
        }

        usort($prioritisedListQuery, function ($a, $b) {
            return $a['priority'] - $b['priority'];
        });

        $zoneData = $prioritisedListQuery[0]['data'];

        $this->setDecision([
            'valid'             => true,
            'locale'            => is_string($zoneData['locale']) ? $zoneData['locale'] : null,
            'country'           => is_string($zoneData['countryIso']) ? $zoneData['countryIso'] : null,
            'language'          => is_string($zoneData['languageIso']) ? $zoneData['languageIso'] : null,
            'url'               => is_string($zoneData['homeUrl']) ? $zoneData['homeUrl'] : null,
            'redirectorOptions' => $redirectorOptions
        ]);

    }

    /**
     * @param string      $locale
     * @param string|null $countryIso
     * @param bool        $countryStrictMode
     * @param bool        $languageStrictMode
     *
     * @return bool|mixed|null
     */
    public function findUrlInZoneTree($locale, $countryIso = null, bool $countryStrictMode = true, $languageStrictMode = false)
    {
        try {
            $zoneDomains = $this->zoneManager->getCurrentZoneDomains(true);
        } catch (\Exception $e) {
            return false;
        }

        if (!is_array($zoneDomains)) {
            return false;
        }

        $locale = $languageStrictMode ? substr($locale, 0, 2) : $locale;

        if ($countryIso === null) {
            $indexId = array_search($locale, array_column($zoneDomains, 'locale'));
            return $indexId !== false ? $zoneDomains[$indexId] : null;
        }

        if ($countryStrictMode === true) {

            // first try to find language iso + guessed country
            // we need to overrule users accepted region fragment by our guessed country
            $language = strpos($locale, '_') !== false ? substr($locale, 0, 2) : $locale;

            $strictLocale = sprintf('%s_%s', $language, $countryIso);
            $indexId = array_search($strictLocale, array_column($zoneDomains, 'locale'));

            return $indexId !== false ? $zoneDomains[$indexId] : null;
        }

        $indexId = array_search($locale, array_column($zoneDomains, 'locale'));

        return $indexId !== false ? $zoneDomains[$indexId] : null;
    }
}
