# Custom Locale Adapter

In most cases there is no need of a custom adapter since both default adapters are sufficient for your daily business.
If you're using complex zones, however, you may want to deliver different data for each zone.

### 1. Create a Service

```yaml
# config/services.yaml
App\Services\I18nBundle\LocaleAdapter\SpecialLocaleProvider:
    parent: I18nBundle\Adapter\LocaleProvider\AbstractLocale
    decorates: I18nBundle\Adapter\LocaleProvider\SystemLocaleProvider # use a decorator
    public: false
    arguments:
        - '@App\Services\I18nBundle\LocaleAdapter\SpecialLocaleProvider.inner'
    tags:
        - { name: i18n.adapter.locale, alias: special }
```

### 2. Set Locale Adapter in your Configuration

```yaml
# config/config.yaml
i18n:
    mode: country
    locale_adapter: special
```

### 3. Create a class

Create a class, extend it from `AbstractLocale`.
In this example, we'll remove some locales if we're in a different zone (example code below).
We're also using the `system` adapter as a decorator, so we don't have to implement all methods again.

```php
<?php

namespace App\Services\I18nBundle\LocaleAdapter;

use I18nBundle\Adapter\LocaleProvider\AbstractLocaleProvider;
use I18nBundle\Adapter\LocaleProvider\SystemLocaleProvider;

class SpecialLocaleProvider extends AbstractLocale
{
    protected SystemLocaleProvider $systemLocaleProvider;

    public function __construct(SystemLocaleProvider $systemLocaleProvider)
    {
        $this->systemLocaleProvider = $systemLocaleProvider;
    }

    public function getDefaultLocale(array $zoneDefinition): ?string
    {
        return $this->systemLocaleProvider->getDefaultLocale($zoneDefinition);
    }

    public function getActiveLocales(array $zoneDefinition): array
    {
        // get default locales
        $validLocales = $this->systemLocaleProvider->getActiveLocales($zoneDefinition);

        // remove some locales in zone 4
        if ($this->currentZoneId === 4) {
            unset($validLocales[0]);
            unset($validLocales[1]);
        }

        return $validLocales;
    }

    public function getLocaleData(array $zoneDefinition, $locale = '', $field = null, $keyIdentifier = 'locale'): mixed
    {
        return $this->systemLocaleProvider->getLocaleData($zoneDefinition, $locale, $field, $keyIdentifier);
    }

    public function getGlobalInfo(array $zoneDefinition): array
    {
        return $this->systemLocaleProvider->getGlobalInfo($zoneDefinition);
    }
}
```
