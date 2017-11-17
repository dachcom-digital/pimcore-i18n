# Code Examples
Depending on your selected mode (language|country) there are several view helpers available.

## Global 

```twig
{# get current mode #}
{{ i18n_zone_info('mode') }}

{# get current language iso #}
{{ dump(i18n_context('getCurrentLanguageIso')) }}

{# get linked languages #}
{{ dump(i18n_context('getLinkedLanguages')) }}

{# get linked languages [true] only rootDocuments #}
{{ dump(i18n_context('getLinkedLanguages', [true])) }}
```

### Current Context (global)
To get data from current context you may want to use the `getCurrentContextInfo` method. 
Since the current context gets located via the current locale, be sure that locale is always available. 
You can also pass it as a second argument.

```twig
{# get current context info: no locale passed, the method will search within the current document - if available #}
{{ dump(i18n_context('getCurrentContextInfo', ['url'])) }}

{# get current context info with current request locale #}
{{ dump(i18n_context('getCurrentContextInfo', ['localeUrlMapping', app.request.locale])) }}
```

Available Options for the `getCurrentContextInfo` context helper:

| Name | Description |
|------|-------------|
| host | For example: `www.pimcore5-domain4.dev` |
| realHost | For example: `pimcore5-domain4.dev` |
| locale | For example: `de_CH` |
| countryIso | For example: `CH` |
| languageIso | For example: `de` |
| hrefLang | For example: `de-ch` |
| localeUrlMapping | For example: `de-ch`. Mostly used to build [static routes](https://github.com/dachcom-digital/pimcore-i18n/blob/master/docs/28_StaticRoutes.md#naming-convention-in-country-context). |
| url | For example: `https://pimcore5-domain4.dev/de-ch` |
| domainUrl | For example: `https://pimcore5-domain4.dev` |
| fullPath | For example: `domain4/de-ch` |
| type | For example: `hardlink` |

## Language

```twig
{# get current language iso #}
{{ dump(i18n_context('getCurrentLanguageIso')) }}

{# get active languages #}
{{ dump(i18n_context('getActiveLanguages')) }}

{# get current language info (id) #}
{{ dump(i18n_context('getCurrentLanguageInfo', ['id'])) }}
```
## Country

```twig
{# get current language iso #}
{{ dump(i18n_context('getCurrentLanguageIso')) }}

{# get current country iso #}
{{ dump(i18n_context('getCurrentCountryIso')) }}

{# get active country localizations #}
{{ dump(i18n_context('getActiveCountries')) }}

{# get current country info (id) #}
{{ dump(i18n_context('getCurrentCountryInfo', ['id'])) }}

{# get country name by iso code #}
{{ dump(i18n_context('getCountryNameByIsoCode', [ i18n_context('getCurrentCountryIso') ])) }}
```
Available Options for the `getCurrentCountryInfo` or `getCurrentLanguageInfo` context (from system adapter):

> Please note: you probably never need those two methods. 
> But if you're using a custom language or country adapter, you might find it helpful. 

| Name | Available | Description |
|------|-----------|-------------|
| id | country, language | Id |
| isoCode | country, language | Iso Code |
| zone | country | Zone (NULL in system adapter) |
| object | country | Object (NULL in system adapter) |

## Implementation in PHP

```php
<?php

namespace AppBundle\Service;

use I18nBundle\Manager\ContextManager;
use Symfony\Component\HttpFoundation\RequestStack;

class AppLocaleHelper
{
    protected $manager;
    protected $requestStack;

    public function __construct(ContextManager $manager, RequestStack $requestStack)
    {
        $this->manager = $manager;
        $this->requestStack = $requestStack;
    }

    public function getGlobalVars()
    {         
        # global
        $currentLanguageIso = $this->manager->getContext()->getCurrentLanguageIso();
        
        $locale = $this->requestStack->getCurrentRequest()->getLocale();
        //locale parameter is optional: use it if you have some static routes without parent documents
        $currentContextInfo = $this->manager->getContext()->getCurrentContextInfo('localeUrlMapping', $locale);
        
        # if language mode
        $currentLanguageIso = $this->manager->getContext()->getCurrentLanguageIso();
        $activeLanguages = $this->manager->getContext()->getActiveLanguages();
        $currentLanguageId = $this->manager->getContext()->getCurrentLanguageInfo('id');
        
        # if country mode
        $currentCountryIso = $this->manager->getContext()->getCurrentCountryIso();
        $activeLanguages = $this->manager->getContext()->getActiveLanguagesForCountry();
        $currentCountryId = $this->manager->getContext()->getCurrentCountryInfo('id');
    }
}
```

## Implementation Examples

```html
{# create a language dropdown #}
<nav id="navigation">
    <select>
        {% for language in i18n_context('getActiveLanguages') %}
            <option {{ (i18n_context('getCurrentLanguageIso') == language.iso) ? 'selected' }} value="{{ language.href }}">{{ language.iso|upper }}</option>
        {% endfor %}
    </select>
</nav>


```

