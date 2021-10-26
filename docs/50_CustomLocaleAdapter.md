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
    protected ?array $validLocales = null;

    public function __construct(SystemLocaleProvider $systemLocaleProvider)
    {
        $this->systemLocaleProvider = $systemLocaleProvider;
    }

    public function getDefaultLocale(): ?string
    {
        return $this->systemLocaleProvider->getDefaultLocale();
    }

    public function getActiveLocales(): array
    {
        // get default locales
        $validLocales = $this->systemLocaleProvider->getActiveLocales();

        // remove some locales in zone 4
        if ($this->currentZoneId === 4) {
            unset($validLocales[0]);
            unset($validLocales[1]);
        }

        return $validLocales;
    }

    public function getLocaleData($isoCode = '', $field = null, $keyIdentifier = 'locale'): mixed
    {
        return $this->systemLocaleProvider->getLocaleData($isoCode, $field, $keyIdentifier);
    }

    public function getGlobalInfo(): array
    {
        return $this->systemLocaleProvider->getGlobalInfo();
    }
}
```
