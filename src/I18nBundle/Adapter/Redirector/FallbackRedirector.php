<?php

namespace I18nBundle\Adapter\Redirector;

use I18nBundle\Manager\ZoneManager;

class FallbackRedirector extends AbstractRedirector
{
    protected ?string $guessedLocale = null;
    protected ?string $guessedLanguage = null;
    protected ?string $guessedCountry = null;
    protected ZoneManager $zoneManager;

    public function __construct(ZoneManager $zoneManager)
    {
        $this->zoneManager = $zoneManager;
    }

    public function makeDecision(RedirectorBag $redirectorBag): void
    {
        if ($this->lastRedirectorWasSuccessful($redirectorBag) === true) {
            return;
        }

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

    protected function findUrlInZoneTree(?string $fallBackLocale): ?string
    {
        try {
            $zoneDomains = $this->zoneManager->getCurrentZoneDomains(true);
        } catch (\Exception $e) {
            return null;
        }

        if (!is_array($zoneDomains)) {
            return null;
        }

        $indexId = array_search($fallBackLocale, array_column($zoneDomains, 'locale'), true);

        if ($indexId === false) {
            return null;
        }

        $docData = $zoneDomains[$indexId];

        $this->guessedLocale = $docData['locale'];
        $this->guessedLanguage = $docData['languageIso'];
        $this->guessedCountry = $docData['countryIso'];

        return $docData['homeUrl'];
    }
}
