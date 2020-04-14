<?php

namespace I18nBundle\Adapter\Redirector;

use I18nBundle\Helper\UserHelper;
use I18nBundle\Manager\ZoneManager;

class GeoRedirector extends AbstractRedirector
{
    /**
     * @var null|string
     */
    protected $guessedLocale;

    /**
     * @var null|string
     */
    protected $guessedLanguage;

    /**
     * @var null|string
     */
    protected $guessedCountry;

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

        $userLanguagesIso = $this->userHelper->getLanguagesAcceptedByUser();
        $userCountryIso = false;

        if (count($userLanguagesIso) === 0) {
            $this->setDecision(['valid' => false, 'redirectorOptions' => [
                'geoLanguage' => false,
                'geoCountry'  => $userCountryIso,
            ]]);

            return;
        }


        foreach($userLanguagesIso as $userLanguageIso) {
            $url = null;

            $redirectorOptions = [
                'geoLanguage' => $userLanguageIso,
                'geoCountry'  => $userCountryIso,
            ];

            if ($redirectorBag->getI18nMode() === 'country') {
                $userCountryIso = $this->userHelper->guessCountry();
                $redirectorOptions['geoCountry'] = $userCountryIso;
                if ($userCountryIso === false) {
                    $this->setDecision(['valid' => false, 'redirectorOptions' => $redirectorOptions]);

                    return;
                }
            }

            if ($redirectorBag->getI18nMode() === 'language') {
                $url = $this->findUrlInZoneTree($userLanguageIso);
            } elseif ($redirectorBag->getI18nMode() === 'country') {
                $userCountryIso = $this->userHelper->guessCountry();
                $url = $this->findUrlInZoneTree($userLanguageIso, $userCountryIso);
            }

            $valid = !empty($url);
            if($valid) {
                break;
            }
        }

        $this->setDecision([
            'valid'             => $valid,
            'locale'            => is_string($this->guessedLocale) ? $this->guessedLocale : null,
            'country'           => is_string($this->guessedCountry) ? $this->guessedCountry : null,
            'language'          => is_string($this->guessedLanguage) ? $this->guessedLanguage : null,
            'url'               => is_string($url) ? $url : null,
            'redirectorOptions' => $redirectorOptions
        ]);
    }

    /**
     * Returns absolute Url to website with language context.
     * Because this could be a different domain, absolute url is necessary.
     *
     * @param null $languageIso
     * @param null $countryIso
     *
     * @return bool
     */
    public function findUrlInZoneTree($languageIso, $countryIso = null)
    {
        try {
            $zoneDomains = $this->zoneManager->getCurrentZoneDomains(true);
        } catch (\Exception $e) {
            return false;
        }

        if (!is_array($zoneDomains)) {
            return false;
        }

        $indexId = false;

        if (empty($countryIso)) { // search in language context
            $indexId = array_search($languageIso, array_column($zoneDomains, 'languageIso'));
        } else { // search in country context
            // check if country and language match
            $index = array_keys(array_filter(
                $zoneDomains,
                function ($v) use ($languageIso, $countryIso) {
                    return $v['languageIso'] === $languageIso && $v['countryIso'] === $countryIso;
                }
            ));

            //check if only language is available.
            if (empty($index)) {
                $index = array_keys(array_filter(
                    $zoneDomains,
                    function ($v) use ($languageIso) {
                        return $v['languageIso'] === $languageIso;
                    }
                ));
            }

            if (isset($index[0])) {
                $indexId = $index[0];
            }
        }

        if ($indexId === false) {
            return false;
        }

        $docData = $zoneDomains[$indexId];

        $this->guessedLocale = $docData['locale'];
        $this->guessedLanguage = $docData['languageIso'];
        $this->guessedCountry = $docData['countryIso'];

        return $docData['homeUrl'];
    }
}
