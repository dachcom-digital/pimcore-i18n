<?php

/*
 * This source file is available under two different licenses:
 *   - GNU General Public License version 3 (GPLv3)
 *   - DACHCOM Commercial License (DCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) DACHCOM.DIGITAL AG (https://www.dachcom-digital.com)
 * @license    GPLv3 and DCL
 */

namespace I18nBundle\Adapter\Redirector;

use I18nBundle\Model\ZoneSiteInterface;

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

        if (!$zoneSite instanceof ZoneSiteInterface) {
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

    protected function findZoneSite(RedirectorBag $redirectorBag): ?ZoneSiteInterface
    {
        $fallbackLocale = $redirectorBag->getI18nContext()->getZoneDefaultLocale();

        try {
            $zoneSites = $redirectorBag->getI18nContext()->getZone()->getSites(true);
        } catch (\Exception $e) {
            return null;
        }

        $indexId = array_search($fallbackLocale, array_map(static function (ZoneSiteInterface $site) {
            return $site->getLocale();
        }, $zoneSites), true);

        if ($indexId === false) {
            return null;
        }

        return $zoneSites[$indexId];
    }
}
