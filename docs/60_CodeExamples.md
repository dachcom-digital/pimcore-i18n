# Code Examples

## Zone Info
This Service helps you to get information about your current zone.
If no zones are configured, you'll get the default settings.

**Twig**
```twig
{# get current mode #}
{{ i18n_zone_info('mode') }}
```

**PHP**
```twig
<?php

use I18nBundle\Manager\ZoneManager;

class ExampleService
{
    protected $zoneManager;

    public function __construct(ZoneManager $zoneManager)
    {
        $this->zoneManager = $zoneManager;
    }

    public function getInformation()
    {
        // returns 'language' or 'country'
        $currentZoneMode = $this->zoneManager->getCurrentZoneInfo('mode');
    }
}
```

## Context Info
This Service helps you to get information from your current context.

### Current Context Information
To get data from current context you may want to use the `getCurrentContextInfo` method. 
Since the current context gets defined via the current locale, be sure that locale is always available.

**Twig**
```twig
{# get current context info #}
{{ dump(i18n_context('getCurrentContextInfo', ['url'])) }}

{# get current context info #}
{{ dump(i18n_context('getCurrentContextInfo', ['localeUrlMapping'])) }}
```

**PHP**
```twig
<?php

use I18nBundle\Manager\ContextManager;

class ExampleService
{
    protected $contextManager;

    public function __construct(ContextManager $contextManager)
    {
        $this->contextManager = $contextManager;
    }

    public function getInformation()
    {
        $currentContextInfo = $this->contextManager->getContext()->getCurrentContextInfo('url');
        $currentContextInfo = $this->contextManager->getContext()->getCurrentContextInfo('localeUrlMapping');
    }
}
```

Available Options for the `getCurrentContextInfo` context helper:

| Name | Description |
|------|-------------|
| host | For example: `www.pimcore5-domain4.test` |
| realHost | For example: `pimcore5-domain4.test` |
| locale | For example: `de_CH` |
| countryIso | For example: `CH` |
| languageIso | For example: `de` |
| hrefLang | For example: `de-ch` |
| localeUrlMapping | For example: `de-ch`. Mostly used to build [static routes](https://github.com/dachcom-digital/pimcore-i18n/blob/master/docs/28_StaticRoutes.md#naming-convention-in-country-context). |
| url | For example: `https://pimcore5-domain4.test/de-ch` |
| domainUrl | For example: `https://pimcore5-domain4.test` |
| fullPath | For example: `domain4/de-ch` |
| type | For example: `hardlink` |

## Specific Current Context
This Service helps you to get transformed data from your current context.
There are two context services available:

- language
- country (language & country)

## Global Information
Some of the context methods are available in both services:

**Twig**
```twig
{# get current locale #}
{{ dump(i18n_context('getCurrentLocale')) }}

{# get current language iso #}
{{ dump(i18n_context('getCurrentLanguageIso')) }}

{# get linked languages #}
{{ dump(i18n_context('getLinkedLanguages')) }}

{# get linked languages [true] only rootDocuments #}
{{ dump(i18n_context('getLinkedLanguages', [true])) }}

{# get language name by iso code #}
{{ dump(i18n_context('getLanguageNameByIsoCode', [ i18n_context('getCurrentLanguageIso'), i18n_context('getCurrentLocale') ])) }}

```

**PHP**
```twig
<?php

use I18nBundle\Manager\ContextManager;

class ExampleService
{
    protected $contextManager;

    public function __construct(ContextManager $contextManager)
    {
        $this->contextManager = $contextManager;
    }

    public function getInformation()
    {
        // get current locale
        $currentLocale = $this->contextManager->getContext()->getCurrentLocale();

        // get current language iso
        $currentLanguageIso = $this->contextManager->getContext()->getCurrentLanguageIso();

        // get linked languages
        $linkedLanguages = $this->contextManager->getContext()->getLinkedLanguages();

        // get linked languages, but only rootDocuments
        $linkedLanguages = $this->contextManager->getContext()->getLinkedLanguages(true);

        // get language name by iso code
        $languageName = $this->contextManager->getContext()->getLanguageNameByIsoCode($currentLanguageIso, $currentLocale);

    }
}
```

### Language Context Information
These methods are only available in the `language` context:

```twig

{# get active languages #}
{{ dump(i18n_context('getActiveLanguages')) }}

{# get current language info (id) #}
{{ dump(i18n_context('getCurrentLanguageInfo', ['id'])) }}
```

**PHP**
```twig
<?php

use I18nBundle\Manager\ContextManager;

class ExampleService
{
    protected $contextManager;

    public function __construct(ContextManager $contextManager)
    {
        $this->contextManager = $contextManager;
    }

    public function getInformation()
    {
        // note: instead of "getContext()" it's possible to use "getLanguageContext()"

        // get active languages
        $activeLanguages = $this->contextManager->getContext()->getActiveLanguages();

        // get current language info (id)
        $currentLanguageId = $this->contextManager->getContext()->getCurrentLanguageInfo('id');

    }
}
```

### Country Context Information

```twig

{# get current country iso #}
{{ dump(i18n_context('getCurrentCountryIso')) }}

{# get active country localizations #}
{{ dump(i18n_context('getActiveCountries')) }}

{# get current country info (id) #}
{{ dump(i18n_context('getCurrentCountryInfo', ['id'])) }}

{# get country name by iso code #}
{{ dump(i18n_context('getCountryNameByIsoCode', [ i18n_context('getCurrentCountryIso'), i18n_context('getCurrentLocale') ])) }}
```

**PHP**
```twig
<?php

use I18nBundle\Manager\ContextManager;

class ExampleService
{
    protected $contextManager;

    public function __construct(ContextManager $contextManager)
    {
        $this->contextManager = $contextManager;
    }

    public function getInformation()
    {
        // note: instead of "getContext()" it's possible to use "getCountryContext()"

        // get current locale
        $currentLocale = $this->contextManager->getContext()->getCurrentLocale();

        // get current language iso
        $currentLanguageIso = $this->contextManager->getContext()->getCurrentLanguageIso();

        // get current country iso
        $currentCountryIso = $this->contextManager->getContext()->getCurrentCountryIso();

        // get active countries
        $activeCountries = $this->contextManager->getContext()->getActiveCountries();

        // get current language info (id)
        $currentCountryId = $this->contextManager->getContext()->getCurrentCountryInfo('id');

        // get language name by iso code
        // basically the same as in global context but you're able to pass the country iso also
        $languageName = $this->contextManager->getContext()->getLanguageNameByIsoCode($currentLanguageIso, $currentLocale, $currentCountryIso);

        // get country name by iso code
        $countryName = $this->contextManager->getContext()->getCountryNameByIsoCode($currentCountryIso, $currentLocale);

    }
}
```

Available Options for the `getCurrentCountryInfo` or `getCurrentLanguageInfo` context (from system adapter):

> Please note: you probably never need those two methods. 
> But if you're using a custom locale adapter, you might find it helpful.

| Name | Description |
|------|-------------|
| id | Id |
| locale | Iso Code |
| isoCode | Iso Code |

## Implementation Examples

### Language Drop-Down
```twig
<nav id="navigation">
    <select>
        {% for language in i18n_context('getActiveLanguages') %}
            <option {{ language.active ? 'selected' : '' }} value="{{ language.linkedHref }}">{{ language.iso|upper }}</option>
        {% endfor %}
    </select>
</nav>
```

### Country Selection
```twig
<nav id="navigation">
    {% for country in i18n_context('getActiveCountries') %}
        <ul>
            <li class="country">{{ country.countryTitle }}
                <ul class="languages">
                    {% for language in country.languages %}
                        <li{{ language.active ? ' class="active"' : '' }}><a href="{{ language.linkedHref }}">{{ language.iso|upper }}</a></li>
                    {% endfor %}
                </ul>
            </li>
        </ul>
    {% endfor %}
</nav>
```

### Complex Country / Language Selection based on Current Zone
```twig
<nav id="navigation">
{% if i18n_zone_info('mode') == 'country' %}
    {% for country in i18n_context('getActiveCountries') %}
        <ul>
            <li class="country">{{ country.countryTitle }}
                <ul class="languages">
                    {% for language in country.languages %}
                        <li{{ language.active ? ' class="active"' : '' }}><a href="{{ language.linkedHref }}">{{ language.iso|upper }}</a></li>
                    {% endfor %}
                </ul>
            </li>
        </ul>
    {% endfor %}
{% elseif i18n_zone_info('mode') == 'language' %}
    <select>
        {% for language in i18n_context('getActiveLanguages') %}
            <option {{ language.active ? 'selected' : '' }} value="{{ language.linkedHref }}">{{ language.iso|upper }}</option>
        {% endfor %}
    </select>
{% endif %}
</nav>
```