# Code Examples

## Zone Info
This Service helps you to get information about your current zone.
If no zones are configured, you'll get the default settings.

The current zone represents an instance of `I18nZoneInterface` which comes with some helper methods:

| Name | Description |
|------|-------------|
| getZoneId | given zone id (null, if it no zones have been configured |
| getZoneName | given zone name (null, if no zones have been configured |
| getZoneDomains | all available domains for given zone. |
| getMode | returns `language` or `country` |
| getTranslations | array, translations for static routes |
| getContext | get current context, see [below](./60_CodeExamples.md#zone-context-information) |
| getSites(bool $flatten = false) | returns all available sites of given zone |
| isActiveZone | bool |
| getLocaleUrlMapping | array |
| getCurrentSite | get current site, see [below](./60_CodeExamples.md#zone-current-site-information) |
| getCurrentLocale | shortcut helper (calls `context->getLocale`) to get current locale |
| getCurrentCountryAndLanguage(bool $returnAsString = true) | get country and locale info |
| getLinkedLanguages(bool $onlyShowRootLanguages = false) | get all (raw) linked documents of current route |
| getActiveLanguages | helper method to list all active languages of current route (see [navigation code example](./60_CodeExamples.md#navigation-examples)) |
| getActiveCountries | helper method to list all active countries and languages of current route (see [navigation code example](./60_CodeExamples.md#navigation-examples)) |
| getLanguageNameByIsoCode(string $languageIso, ?string $locale = null) | helper to get language name by iso code |
| getCountryNameByIsoCode(string $countryIso, ?string $locale = null) | helper to get country name by iso code |
| *Locale Provider Helper Below:* | |
|  -> getCurrentLocaleInfo(string $field) | shortcut helper (calls `localeProvider->getLocaleData` and passes `context->getLocale`) to get detailed information about active locale |
|  -> getLocaleProviderLocaleInfo(string $locale, string $field) | mixed, get locale info of given locale from locale provider |
|  -> getLocaleProviderDefaultLocale | For example: `de_DE` |
|  -> getLocaleProviderActiveLocales | array, all active locales which are available and valid for current zone |
|  -> getLocaleProviderGlobalInfo | array, international state |

**Twig**
```twig
{# 
    be careful, i18n_zone is allowed to return null!
#}

{% set current_locale = i18n_zone().context.locale %}
{% set current_language_iso = i18n_zone().context.languageIso %}
{% set current_country_iso = i18n_zone().context.countryIso %}

{{ dump(i18n_zone().mode) }}
{{ dump(i18n_zone().context.locale) }}
{{ dump(i18n_zone().linkedLanguages) }}
{{ dump(i18n_zone().activeLanguages) }}
{{ dump(i18n_zone().activeCountries) }}
{{ dump(i18n_zone().languageNameByIsoCode(current_language_iso, current_locale)) }}
{{ dump(i18n_zone().countryNameByIsoCode(current_country_iso, current_locale)) }}

{{ dump(i18n_zone().localeProviderActiveLocales()) }}
{{ dump(i18n_zone().localeProviderLocaleInfo('de', 'id')) }}
```

**PHP**
```php
<?php

use Symfony\Component\HttpFoundation\RequestStack;
use I18nBundle\Http\ZoneResolverInterface;

class ExampleService
{
    protected RequestStack $requestStack;
    protected ZoneResolverInterface $zoneResolver;

    public function __construct(RequestStack $requestStack, ZoneResolverInterface $zoneResolver)
    {
        $this->requestStack = $requestStack;
        $this->zoneResolver = $zoneResolver;
    }

    public function getInformation()
    {
        $zone = $this->zoneResolver->getZone($this->requestStack->getMainRequest());
        
        $mode = $zone?->getMode();
        $LinkedLanguages = $zone?->getLinkedLanguages();
        
        // get current locale
        $currentLocale = $zone->getContext()->getLocale();

        // get current language iso
        $currentLanguageIso = $zone->getContext()->getLanguageIso();

        // get linked languages
        $linkedLanguages = $zone->getLinkedLanguages();

        // get linked languages, but only rootDocuments
        $linkedLanguages = $zone->getLinkedLanguages(true);

        // get language name by iso code
        $languageName = $zone->getContext()->getLanguageNameByIsoCode($currentLanguageIso, $currentLocale);
        
        // locale provider info
        $localProviderActiveLocales = $zone->getLocaleProviderActiveLocales();
        $localeProviderLocaleInfo = $zone->getLocaleProviderLocaleInfo('de', 'id');
    }
}
```

### Zone Current Site Information
To retrieve data from an active site, you may want to use the `getCurrentSite()` method. 
Since the current site gets defined via the current locale, be sure that locale is always available.

The current site represents an instance of `I18nZoneSiteInterface` which comes with some helper methods:

| Name | Description |
|------|-------------|
| getHost | For example: `www.pimcore5-domain4.test` |
| getRealHost | For example: `pimcore5-domain4.test` |
| isRootDomain | bool |
| getLocale | For example: `de_CH` |
| getCountryIso | For example: `CH` |
| getLanguageIso | For example: `de` |
| getHrefLang | For example: `de-ch` |
| getLocaleUrlMapping | For example: `de-ch`. Mostly used to build [static routes](./28_StaticRoutes.md#naming-convention-in-country-context). |
| getUrl | For example: `https://pimcore5-domain4.test/de-ch` |
| getHomeUrl | string |
| getDomainUrl | For example: `https://pimcore5-domain4.test` |
| getFullPath | For example: `domain4/de-ch` |
| getType | For example: `hardlink` |
| getSubSites | array |
| hasSubSites | bool |

**Twig**
```twig
{# get current context info #}
{{ dump(i18n_zone().currentSite.url) }}
{{ dump(i18n_zone().currentSite.localeUrlMapping) }}
```

**PHP**
```php
<?php

use Symfony\Component\HttpFoundation\RequestStack;
use I18nBundle\Http\ZoneResolverInterface;

class ExampleService
{
    protected RequestStack $requestStack;
    protected ZoneResolverInterface $zoneResolver;

    public function __construct(RequestStack $requestStack, ZoneResolverInterface $zoneResolver)
    {
        $this->requestStack = $requestStack;
        $this->zoneResolver = $zoneResolver;
    }

    public function getInformation()
    {
        $zone = $this->zoneResolver->getZone($this->requestStack->getMainRequest());
        
        $currentContextInfo = $zone->getCurrentSite()->getUrl();
        $currentContextInfo = $zone->getCurrentSite()->getLocaleUrlMapping();
    }
}
```

### Zone Context Information
Zone context always holds **active** locale information about the **current** zone (mostly based on current request) like the current locale.

The zone context represents an instance of `I18nContextInterface` which comes with some helper methods:

| Name | Description |
|------|-------------|
| hasLocale | returns false if locale is null |
| getLocale | For example: `de_DE` |
| hasLanguageIso | returns false if language iso is null |
| getLanguageIso | For example: `de` |
| hasCountryIso | returns false if country iso is null |
| getCountryIso | For example: `DE` |
| isValidZoneLocale | bool |

**Twig**
```twig
{{ dump(i18n_zone().context.locale) }}
{{ dump(i18n_zone().context.countryIso) }}
```

## Navigation Examples

### Language Drop-Down
```twig
{% set i18n_zone = i18n_zone() %}
{% if i18n_zone is not null %}
    {% set languages = i18n_zone.activeLanguages %}
    {% if languages is iterable %}
        <nav id="navigation">
            <select>
                {% for language in languages %}
                    <option {{ language.active ? 'selected' : '' }} value="{{ language.linkedHref }}">{{ language.iso|upper }}</option>
                {% endfor %}
            </select>
        </nav>
    {% endif %}
{% endif %}
```

### Country Selection
```twig
{% set i18n_zone = i18n_zone() %}
{% if i18n_zone is not null %}
    {% set countries = i18n_zone.activeCountries %}
    {% if countries is iterable %}
        <nav id="navigation">
            {% for country in countries %}
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
    {% endif %}
{% endif %}
```

### Complex Country / Language Selection based on Current Zone
```twig
<nav id="navigation">
    {% set i18n_zone = i18n_zone() %}
    {% if i18n_zone is not null %}
        {% if i18n_zone.mode == 'country' %}
            {% set countries = i18n_zone.activeCountries %}
            {% if countries is iterable %}
                {% for country in countries %}
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
            {% endif %}
        {% elseif i18n_zone.mode == 'language' %}
            {% set languages = i18n_zone.activeLanguages %}
            {% if languages is iterable %}
                <select>
                    {% for language in languages %}
                        <option {{ language.active ? 'selected' : '' }} value="{{ language.linkedHref }}">{{ language.iso|upper }}</option>
                    {% endfor %}
                </select>
            {% endif %}
        {% endif %}
    {% endif %}
</nav>
```