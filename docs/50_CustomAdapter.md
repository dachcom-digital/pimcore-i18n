# Custom Adapter

In most cases, there is no need of a custom adapter since both default adapter are sufficient for your daily business.
If you're using complex zones, however, you may want to deliver different data for each zone.

We're only show you a example with countries, if you need to do this with languages, it's nearly the same.

### 1. Create a Service

```twig
{# in app/config/services.yml #}
app.i18n.adapter.country.special:
    public: true
    class: AppBundle\Services\I18nBundle\CountryAdapter\Special
    tags:
        - { name: i18n.adapter.country }
```

### 2. Create a class

Create a class, extend it from `AbstractCountry` and implement the `CountryInterface`.
In this example, we define some countries and we'll deliver some other countries if we're in a different zone.

```php
<?php

namespace AppBundle\Services\I18nBundle\CountryAdapter;

use I18nBundle\Adapter\Country\AbstractCountry;
use I18nBundle\Adapter\Country\CountryInterface;

class Special extends AbstractCountry implements CountryInterface
{
    var $countries = [
        [
            'isoCode' => 'AT',
            'id'      => 1,
            'zone'    => NULL,
            'object'  => NULL
        ],
        [
            'isoCode' => 'GLOBAL',
            'id'      => NULL,
            'zone'    => NULL,
            'object'  => NULL
        ]
         

    ];

    var $countries_zone_2 = [
        [
            'isoCode' => 'CH',
            'id'      => 1,
            'zone'    => NULL,
            'object'  => NULL
        ],
        [
            'isoCode' => 'GLOBAL',
            'id'      => NULL,
            'zone'    => NULL,
            'object'  => NULL
        ]
    ];

    public function getActiveCountries(): array
    {
        $countries = $this->countries;

        // current zone id is defined in abstract country.
        if($this->currentZoneId === 2) {
            $countries = $this->countries_zone_2;
        }

        return $countries;
    }

    public function getCountryData($isoCode = '', $field = NULL)
    {
        $config = $this->countries;

        //no info for global, which means: international like (de,en)!
        if ($isoCode === 'GLOBAL') {
            return NULL;
        }

        if (isset($config[$isoCode])) {
            if ($field && isset($config[$isoCode][$field])) {
                return $config[$isoCode][$field];
            }

            return $config[$isoCode];
        }

        return NULL;
    }
    
    public function getGlobalInfo()
    {
        return $this->countries['GLOBAL'];
    }
}
```