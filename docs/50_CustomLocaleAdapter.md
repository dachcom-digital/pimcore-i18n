# Custom Locale Adapter

In most cases, there is no need of a custom adapter since both default adapter are sufficient for your daily business.
If you're using complex zones, however, you may want to deliver different data for each zone.

### 1. Create a Service

```yaml
# in app/config/services.yml
AppBundle\Services\I18nBundle\LocaleAdapter\Special:
    parent: I18nBundle\Adapter\Context\Locale\AbstractLocale
    decorates: I18nBundle\Adapter\Locale\System # use a decorator
    public: false
    arguments:
        - '@AppBundle\Services\I18nBundle\LocaleAdapter\Website.inner'
    tags:
        - { name: i18n.adapter.locale, alias: special }
```

### 2. Set Locale Adapter in your Configuration

```yaml
# in app/config/config.yml
i18n:
    mode: country
    locale_adapter: special
```

### 3. Create a class

Create a class, extend it from `AbstractLocale`.
In this example, we'lll remove some locales if we're in a different zone (example code below).
We're also using the `system` adapter as a decorator so we don't have to implement all methods again.

```php
<?php

namespace AppBundle\Services\I18nBundle\LocaleAdapter;

use I18nBundle\Adapter\Locale\AbstractLocale;

class Special extends AbstractLocale
{
    /**
     * @var System
     */
    protected $system;

    /**
     * @var null
     */
    protected $validLocales = null;

    public function __construct(System $system)
    {
        $this->system = $system;
    }

    public function getDefaultLocale()
    {
        return $this->system->getDefaultLocale();
    }

    public function getActiveLocales(): array
    {
        // get default locales
        $validLocales = $this->system->getActiveLocales();

        // remove some locales in zone 4
        if ($this->currentZoneId === 4) {
            unset($validLocales[0]);
            unset($validLocales[1]);
        }

        return $validLocales;
    }

    public function getLocaleData($isoCode = '', $field = null, $keyIdentifier = 'locale')
    {
        return $this->system->getLocaleData($isoCode, $field, $keyIdentifier);
    }

    public function getGlobalInfo()
    {
        return $this->system->getGlobalInfo();
    }
}
```