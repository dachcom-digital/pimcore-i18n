# Custom Adapter

In most cases, there is no need of a custom adapter since both default adapter are sufficient for your daily business.
If you're using complex zones, however, you may want to deliver different data for each zone.

We're only show you a example with countries, if you need to do this with languages, it's nearly the same.

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
In this example, we define some countries and we'll deliver some other countries if we're in a different zone.
We're also using the `system` adapter as a decorator so we don't have to implement all methods again.

```php
<?php

namespace AppBundle\Services\I18nBundle\LocaleAdapter;

use I18nBundle\Adapter\Locale\AbstractLocale;

class Special extends AbstractLocale
{
    var $countries = [
        [
            'isoCode' => 'AT',
            'id'      => 1,
            'zone'    => null,
            'object'  => null
        ],
        [
            'isoCode' => 'GLOBAL',
            'id'      => null,
            'zone'    => null,
            'object'  => null
        ]
    ];

    var $countries_zone_2 = [
        [
            'isoCode' => 'CH',
            'id'      => 1,
            'zone'    => null,
            'object'  => null
        ],
        [
            'isoCode' => 'GLOBAL',
            'id'      => null,
            'zone'    => null,
            'object'  => null
        ]
    ];

    /**
     * @var System
     */
    protected $system;

    /**
     * @var null
     */
    protected $validLanguages = null;

    public function __construct(System $system)
    {
        $this->system = $system;
    }

    public function getDefaultLocale()
    {
        return $this->system->getDefaultLocale();
    }

    public function getActiveLanguages(): array
    {
        return $this->system->getActiveLanguages();
    }

    public function getLanguageData($isoCode = '', $field = null)
    {
        return $this->system->getLanguageData($isoCode, $field);
    }

    public function getActiveCountries(): array
    {
        $countries = $this->countries;

        // current zone id is defined in abstract country.
        if($this->currentZoneId === 2) {
            $countries = $this->countries_zone_2;
        }

        return $countries;
    }

    public function getCountryData($isoCode = '', $field = null)
    {
        return $this->system->getCountryData($isoCode, $field);
    }
    
    public function getGlobalInfo()
    {
        return $this->system->getGlobalInfo();
    }
}
```