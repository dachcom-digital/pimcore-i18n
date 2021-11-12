# Current Context

Fetch the current context to list linked languages for example:

```twig
<h5>i18n Current Context</h5>
{% set i18n_current_context = i18n_current_context() %}
{{ dump(i18n_current_context) }}
{{ dump(i18n_current_context.linkedLanguages) }}
<ul>
    {% for link in i18n_current_context.linkedLanguages %}
        <li>{{ link.url }}</li>
    {% endfor %}
</ul>
```

**PHP**

```php
<?php

use Symfony\Component\HttpFoundation\RequestStack;
use I18nBundle\Http\I18nContextResolverInterface;

class Service
{
    public function myAction(RequestStack $requestStack, I18nContextResolverInterface $i18nContextResolver)
    {
        $i18nContext = $i18nContextResolver->getContext($requestStack->getMainRequest());
        $linkedLanguages = $i18nContext->getLinkedLanguages();
    }
}
```

# Custom Context Look-Up

In some cases, you want to retrieve all available links for a specific document or object.

## Boot Context in TWIG

```twig
<h4>I18n Context Look-Up (document)</h4>
{% set document_context = i18n_create_context_by_entity(pimcore_document(16), { _locale: 'en' }) %}
{{ dump(document_context) }}
{{ dump(document_context.linkedLanguages) }}


<h4>I18n Context Look-Up (object [will call correspondig link generator])</h4>
{% set object_context = i18n_create_context_by_entity(pimcore_object(16), { _locale: 'en' }) %}
{{ dump(object_context) }}
{{ dump(object_context.linkedLanguages) }}


<h4>I18n Context Look-Up (symfony route)</h4>
{% set object_context = i18n_create_context_by_symfony_route('my_symfony_route, { _locale: 'en' } }) %}
{{ dump(object_context) }}
{{ dump(object_context.linkedLanguages) }}


<h4>I18n Context Look-Up (static route)</h4>
{% set object_context = i18n_create_context_by_static_route('my_static_route', { _locale: 'fr', object_id: 16 } }) %}
{{ dump(object_context) }}
{{ dump(object_context.linkedLanguages) }}

{# If you're using zones, you NEED to pass the site as context! #}

<h4>I18n Context Look-Up with Zones (document)</h4>
{% set zone_aware_document_context = i18n_create_context_by_entity(pimcore_document(16), { _locale: 'en' }, pimcore_site_by_domain('my-site.test')) %}
{{ dump(zone_aware_document_context) }}
{{ dump(zone_aware_document_context.linkedLanguages) }}
```

## Boot Context in PHP

```php
<?php

use I18nBundle\Manager\I18nContextManager;
use I18nBundle\Model\RouteItem\RouteItemInterface;

class ExampleService
{
    protected I18nContextManager $i18nContextManager;

    public function __construct(I18nContextManager $i18nContextManager)
    {
        $this->i18nContextManager = $i18nContextManager;
    }

    public function build(Pimcore\DataObject $object, array $routeParameter)
    {
        ## I. no zones defined
        $parameters = [
            'routeParameters' => $routeParameter,
            'entity'          => $object
        ];
        
        // third argument needs to be true to fully boot context (initialize linked zone sites)        
        $i18nContext = $this->i18nContextManager->buildContextByParameters(RouteItemInterface::STATIC_ROUTE, $parameters, true);
       
        ## II. zones available
        ## You MUST pass the site as context!
        $zoneAwareParameters = [
            'routeParameters' => $routeParameter,
            'entity'          => $object,
            'context'         => [
                'site' => $site
            ]
        ];
        
       $zoneAwareI18nContext = $this->i18nContextManager->buildContextByParameters(RouteItemInterface::STATIC_ROUTE, $zoneAwareParameters, true);
       
       dump($zoneAwareI18nContext);
    }
}
```