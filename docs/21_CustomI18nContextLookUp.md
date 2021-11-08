# Custom Context Look-Up
In some cases, you want to retrieve all available links for a specific document or object (Generating links via commandline for example). 

```twig
<h4>I18n Context Look-Up (document)</h4>
{% set document_context = i18n_create_context_by_entity(pimcore_document(16), { _locale: 'en' }) %}
{{ dump(document_context) }}
{{ dump(document_context.linkedLanguages) }}
<hr>
<h4>I18n Context Look-Up (object)</h4>
{% set object_context = i18n_create_context_by_entity(pimcore_object(16), { _locale: 'en', object_id: 16 }) %}
{{ dump(object_context) }}
{{ dump(object_context.linkedLanguages) }}
<hr>
<h4>I18n Context Look-Up (symfony route)</h4>
{% set object_context = i18n_create_context_by_symfony_route('my_symfony_route, { _locale: 'en' } }) %}
{{ dump(object_context) }}
{{ dump(object_context.linkedLanguages) }}
<hr>
<h4>I18n Context Look-Up (static route)</h4>
{% set object_context = i18n_create_context_by_static_route('my_static_route', { _locale: 'fr', object_id: 16 } }) %}
{{ dump(object_context) }}
{{ dump(object_context.linkedLanguages) }}

## If you're using zones, you NEED to pass the site as context!

<h4>I18n Context Look-Up with Zones (document)</h4>
{% set zone_aware_document_context = i18n_create_context_by_entity(pimcore_document(16), { _locale: 'en' }, pimcore_site_by_domain('my-site.test')) %}
{{ dump(zone_aware_document_context) }}
{{ dump(zone_aware_document_context.linkedLanguages) }}
```

```php
<?php

use I18nBundle\Manager\I18nContextManager;

class ExampleService
{
    protected I18nContextManager $i18nContextManager;

    public function __construct(I18nContextManager $i18nContextManager)
    {
        $this->i18nContextManager = $i18nContextManager;
    }

    public function build(Pimcore\Document $document, array $routeParameter)
    {
        $parameters = [
            'routeParameters' => $routeParameter,
            'entity'          => $document
        ];
        
       $i18nContext = $this->i18nContextManager->buildContextByParameters($parameters, true);
       
       ## If you're using zones, you NEED to pass the site as context!
       
       $zoneAwareParameters = [
            'routeParameters' => $routeParameter,
            'entity'          => $document,
            'context'         => [
                'site' => $site
            ]
        ];
        
       $zoneAwareI18nContext = $this->i18nContextManager->buildContextByParameters($zoneAwareParameters, true);
       
       dump($zoneAwareI18nContext);
    }
}
```