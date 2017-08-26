# Code Examples

Depending on your selected mode (language|country) there are several view helpers available.

## Global 

```twig

{# get current mode #}
{{ i18n_zone_info('mode') }}

{# get linked languages #}
{{ dump(i18n_context('getLinkedLanguages')) }}

{# get linked languages [true] only rootDocuments #}
{{ dump(i18n_context('getLinkedLanguages', [true])) }}

```

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

{# get current country info (id) #}
{{ dump(i18n_context('getCurrentCountryInfo', ['id'])) }}

{# get country name by iso code #}
{{ dump(i18n_context('getCountryNameByIsoCode', [ i18n_context('getCurrentCountryIso') ])) }}

{# get active country localizations #}
{{ dump(i18n_context('getActiveCountryLocalizations')) }}

```

## Implementation Examples

```twig
{# create a language dropdown #}
<nav id="navigation">
    <select>
        {% for language in i18n_context('getActiveLanguages') %}
            <option {{ (i18n_context('getCurrentLanguageIso') == language.iso) ? 'selected' }} value="{{ language.href }}">{{ language.iso|upper }}</option>
        {% endfor %}
    </select>
</nav>


```

