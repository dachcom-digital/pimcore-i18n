<?php

namespace I18nBundle\Adapter\Redirector;

use I18nBundle\Helper\UserHelper;
use I18nBundle\Model\ZoneSiteInterface;

class GeoRedirector extends AbstractRedirector
{
    protected UserHelper $userHelper;

    public function __construct(UserHelper $userHelper)
    {
        $this->userHelper = $userHelper;
    }

    public function makeDecision(RedirectorBag $redirectorBag): void
    {
        if ($this->lastRedirectorWasSuccessful($redirectorBag) === true) {
            return;
        }

        /**
         * - Based on string source HTTP_ACCEPT_LANGUAGE ("de,en;q=0.9,de-DE;q=0.8;...")
         * - Transformed by symfony to [de, en, de_DE, ...]
         */
        $userLanguagesIso = $this->userHelper->getLanguagesAcceptedByUser();

        if (count($userLanguagesIso) === 0) {
            $this->setDecision([
                'valid'             => false,
                'redirectorOptions' => [
                    'geoLanguage' => false,
                    'geoCountry'  => false,
                ]
            ]);

            return;
        }

        $userCountryIso = $this->userHelper->guessCountry();
        $zoneSites = $redirectorBag->getI18nContext()->getZone()->getSites(true);

        $redirectorOptions = [
            'geoLanguage' => $userLanguagesIso,
            'geoCountry'  => $userCountryIso ?? false,
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

                if (null !== $zoneSite = $this->findZoneSite($zoneSites, $userLocale, $country, $countryStrictMode, $languageStrictMode)) {
                    $prioritisedListQuery[] = [
                        'priority' => $index === 0 ? -1 : $priority,
                        'site'     => $zoneSite
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

        usort($prioritisedListQuery, static function ($a, $b) {
            return $a['priority'] - $b['priority'];
        });

        /** @var ZoneSiteInterface $zoneSite */
        $zoneSite = $prioritisedListQuery[0]['site'];

        $this->setDecision([
            'valid'             => true,
            'locale'            => $zoneSite->getLocale(),
            'country'           => $zoneSite->getCountryIso(),
            'language'          => $zoneSite->getLanguageIso(),
            'url'               => $zoneSite->getHomeUrl(),
            'redirectorOptions' => $redirectorOptions
        ]);
    }

    protected function findZoneSite(
        array $zoneSites,
        string $locale,
        ?string $countryIso = null,
        bool $countryStrictMode = true,
        bool $languageStrictMode = false
    ): ?ZoneSiteInterface {

        if (!is_array($zoneSites)) {
            return null;
        }

        $locale = $languageStrictMode ? substr($locale, 0, 2) : $locale;

        if ($countryIso === null) {

            $indexId = array_search($locale, array_map(static function (ZoneSiteInterface $site) {
                return $site->getLocale();
            }, $zoneSites), true);

            return $indexId !== false ? $zoneSites[$indexId] : null;
        }

        if ($countryStrictMode === true) {
            // first try to find language iso + guessed country
            // we need to overrule users accepted region fragment by our guessed country
            $language = str_contains($locale, '_') ? substr($locale, 0, 2) : $locale;

            $strictLocale = sprintf('%s_%s', $language, $countryIso);

            $indexId = array_search($strictLocale, array_map(static function (ZoneSiteInterface $site) {
                return $site->getLocale();
            }, $zoneSites), true);

            return $indexId !== false ? $zoneSites[$indexId] : null;
        }

        $indexId = array_search($locale, array_map(static function (ZoneSiteInterface $site) {
            return $site->getLocale();
        }, $zoneSites), true);

        // no site with given locale found
        // maybe there is a matching country site
        if ($indexId === false) {
            $indexId = array_search($countryIso, array_map(static function (ZoneSiteInterface $site) {
                return $site->getCountryIso();
            }, $zoneSites), true);
        }

        return $indexId !== false ? $zoneSites[$indexId] : null;
    }
}
