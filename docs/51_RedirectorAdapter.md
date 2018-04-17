# Redirector Adapter

If a visitor enters your website, i18n tries to find a valid locale based on several conditions.
There are three redirectors implemented. Every redirector triggers (based on priority) a `makeDecision` method.
If the service is able to resolve the redirection, it will return the redirection url. If not, the next
redirector gets applied.

### Cookie Redirector
> Priority: `300`
If enabled, visitor gets redirected to the last selected locale

### GEO Redirector
> Priority: `200`
If enabled, visitor gets redirected based on IP and browser language

### Fallback Redirector
> Priority: `100`
If enabled, visitor gets redirected based on the `default_locale` setting defined in i18n settings (available in each zone)
or by default locale defined in your pimcore system settings.

## Disable a Redirector

```yaml
# in app/config/config.yml
i18n:
    registry:
        redirector:
            cookie:
                enabled: false
```

## Create a Custom Redirector

### 1. Create a Service

```yaml
# in app/config/services.yml
AppBundle\Services\I18nBundle\RedirectorAdapter\Website:
    parent: I18nBundle\Adapter\Redirector\AbstractRedirector
    public: false
    tags:
        # we want to trigger our redirector *before* the fallback comes in
        - { name: i18n.adapter.redirector, alias: website, priority: 110 }
```

### 3. Create a class

Create a class, extend it from `AbstractRedirector`.

```php
<?php

namespace AppBundle\Services\I18nBundle\RedirectorAdapter;

use I18nBundle\Adapter\Redirector\AbstractRedirector;
use I18nBundle\Adapter\Redirector\RedirectorBag;
use I18nBundle\Manager\ZoneManager;

class Website extends AbstractRedirector
{
    protected $zoneManager;

    public function __construct(ZoneManager $zoneManager)
    {
        $this->zoneManager = $zoneManager;
    }

    public function makeDecision(RedirectorBag $redirectorBag)
    {
        // if one of the last decisions was successful, we can skip further work.
        if ($this->lastRedirectorWasSuccessful($redirectorBag) === true) {
            return;
        }

        // get the last decision bag
        $lastDecisionBag = $redirectorBag->getLastRedirectorDecision();

        // now, since the last decision was not successful:
        // we need to find a locale for redirect.
        $decisionName = $lastDecisionBag['name'];
        $decisionData = $lastDecisionBag['decision'];

        // last decision was "geo" as we prioritized our service explicitly.
        // if you're not sure, use the $decisionName to check the last redirector name

        // checkout the geo options:
        $redirectorOptions = $decisionData['redirectorOptions'];

        /*
         * This example assumes that your on localhost:
         * - geoCountry is empty because the "geo" redirector couldn't resolve your country
         *
         * print_r($redirectorOptions);
         *
         * returns an array:
         * Array (
         *   [geoLanguage] => de
         *   [geoCountry] =>
         * )
         *
         */

        // get all valid zone domains
        // and check if there is something we can offer.
        $currentZoneId = $this->zoneManager->getCurrentZoneInfo('zone_id');
        $zoneDomains = $this->zoneManager->getCurrentZoneDomains(true);

        // only do something in zone 4.
        if($currentZoneId !== 4) {
            $this->setDecision(['valid' => false]);
            return;
        }

        // example: if geoCountry is empty,
        // we always want a redirection to "de_CH"
        if (empty($redirectorOptions['geoCountry'])) {

            $indexId = array_search('de_CH', array_column($zoneDomains, 'locale'));

            // we found a valid locale
            if ($indexId !== false) {
                $docData = $zoneDomains[$indexId];
                $this->setDecision([
                    'valid'    => true,
                    'locale'   => $docData['locale'],
                    'country'  => $docData['countryIso'],
                    'language' => $docData['languageIso'],
                    'url'      => $docData['homeUrl']
                ]);
            } else {
                // otherwise we invalidate our decision
                // so the next redirctor ("fallback" in our case) is allowed to find another route.
                $this->setDecision(['valid' => false]);
            }

            // otherwise we invalidate our decision
            // so the next redirctor ("fallback" in our case) is allowed to find another route.
        } else {
            $this->setDecision(['valid' => false]);
        }
    }
}
```
