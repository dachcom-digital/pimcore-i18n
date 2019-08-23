<?php

namespace I18nBundle\Adapter\Redirector;

use I18nBundle\Manager\ZoneManager;

class FallbackRedirector extends AbstractRedirector
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
     * @param ZoneManager $zoneManager
     */
    public function __construct(ZoneManager $zoneManager)
    {
        $this->zoneManager = $zoneManager;
    }

    /**
     * @param RedirectorBag $redirectorBag
     */
    public function makeDecision(RedirectorBag $redirectorBag)
    {
        if ($this->lastRedirectorWasSuccessful($redirectorBag) === true) {
            return;
        }

        $userCountryIso = null;
        $url = null;

        $url = $this->findUrlInZoneTree($redirectorBag->getDefaultLocale());
        $valid = !empty($url);

        $this->setDecision([
            'valid'    => $valid,
            'locale'   => is_string($this->guessedLocale) ? $this->guessedLocale : null,
            'country'  => is_string($this->guessedCountry) ? $this->guessedCountry : null,
            'language' => is_string($this->guessedLanguage) ? $this->guessedLanguage : null,
            'url'      => is_string($url) ? $url : null
        ]);
    }

    /**
     * @param string|null $fallBackLocale
     *
     * @return bool|string
     */
    public function findUrlInZoneTree($fallBackLocale = null)
    {
        try {
            $zoneDomains = $this->zoneManager->getCurrentZoneDomains(true);
        } catch (\Exception $e) {
            return false;
        }

        if (!is_array($zoneDomains)) {
            return false;
        }

        $indexId = array_search($fallBackLocale, array_column($zoneDomains, 'locale'));

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
