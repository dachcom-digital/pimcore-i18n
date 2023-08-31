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

## Define Redirect Status Code
By default, a redirect will be dispatched with code `302`. If you want to change it, you need to update your config:

```yaml
# config/packages/i18n.yaml
i18n:
    redirect_status_code: 301
```

## Disable a Redirector

```yaml
# config/packages/i18n.yaml
i18n:
    registry:
        redirector:
            cookie:
                enabled: false
```

## Create a Custom Redirector

### 1. Create a Service

```yaml
# config/services.yaml
App\Services\I18nBundle\RedirectorAdapter\Website:
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

namespace App\Services\I18nBundle\RedirectorAdapter;

use I18nBundle\Adapter\Redirector\AbstractRedirector;
use I18nBundle\Adapter\Redirector\RedirectorBag;
use I18nBundle\Model\ZoneSiteInterface;

class Website extends AbstractRedirector
{
    public function makeDecision(RedirectorBag $redirectorBag): void
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
         * This example assumes that you're on localhost:
         * - geoCountry is empty because the "geo" redirector couldn't resolve your country
         *
         * dump($redirectorOptions);
         *
         * returns an array:
         * Array (
         *   [geoLanguage] => [de, de_DE, ...]
         *   [geoCountry]  => false
         * )
         *
         */

        // get all valid zone domains
        // and check if there is something we can offer.
        $currentZoneId = $redirectorBag->getI18nContext()->getZone()->getId();
        $zoneSites = $redirectorBag->getI18nContext()->getZone()->getSites(true);

        // only do something in zone 4.
        if($currentZoneId !== 4) {
            $this->setDecision(['valid' => false]);
            return;
        }

        // example: if geoCountry is empty,
        // we always want a redirection to "de_CH"
        if (empty($redirectorOptions['geoCountry'])) {

            $indexId = array_search('de_CH', array_map(static function (ZoneSiteInterface $site) {
                    return $site->getLocale();
            }, $zoneSites), true);
                    
            // we found a valid locale
            if ($indexId !== false) {
                /** @var ZoneSiteInterface $zoneSite */
                $zoneSite = $zoneSites[$indexId];
                $this->setDecision([
                    'valid'    => true,
                    'locale'   => $zoneSite->getLocale(),
                    'country'  => $zoneSite->getCountryIso(),
                    'language' => $zoneSite->getLanguageIso(),
                    'url'      => $zoneSite->getHomeUrl()
                ]);
            } else {
                // otherwise, we invalidate our decision
                // so the next redirector ("fallback" in our case) is allowed to find another route.
                $this->setDecision(['valid' => false]);
            }

            // otherwise, we invalidate our decision
            // so the next redirector ("fallback" in our case) is allowed to find another route.
        } else {
            $this->setDecision(['valid' => false]);
        }
    }
}
```
