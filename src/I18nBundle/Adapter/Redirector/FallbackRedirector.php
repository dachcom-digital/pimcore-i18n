<?php

namespace I18nBundle\Adapter\Redirector;

use I18nBundle\Model\I18nSiteInterface;

class FallbackRedirector extends AbstractRedirector
{
    protected ?string $guessedLocale = null;
    protected ?string $guessedLanguage = null;
    protected ?string $guessedCountry = null;

    public function makeDecision(RedirectorBag $redirectorBag): void
    {
        if ($this->lastRedirectorWasSuccessful($redirectorBag) === true) {
            return;
        }

        $zoneSite = $this->findZoneSite($redirectorBag);

        if (!$zoneSite instanceof I18nSiteInterface) {
            $this->setDecision(['valid' => false]);
            return;
        }

        $this->setDecision([
            'valid'    => true,
            'locale'   => $zoneSite->getLocale(),
            'country'  => $zoneSite->getCountryIso(),
            'language' => $zoneSite->getLanguageIso(),
            'url'      => $zoneSite->getHomeUrl()
        ]);
    }

    protected function findZoneSite(RedirectorBag $redirectorBag): ?I18nSiteInterface
    {
        $fallbackLocale = $redirectorBag->getZone()->getLocaleProvider()->getDefaultLocale();

        try {
            $zoneSites = $redirectorBag->getZone()->getSites(true);
        } catch (\Exception $e) {
            return null;
        }

        if (!is_array($zoneSites)) {
            return null;
        }

        $indexId = array_search($fallbackLocale, array_map(static function (I18nSiteInterface $site) {
            return $site->getLocale();
        }, $zoneSites), true);

        if ($indexId === false) {
            return null;
        }

        return $zoneSites[$indexId];
    }
}
