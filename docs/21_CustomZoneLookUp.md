# Custom Zone Look-Up
In some cases, you want to retrieve all available links for a specific document or object.
Generating links via commandline could be a usecase for example. 

```twig
<h4>I18n Zone Look-Up (document)</h4>
{% set document_zone = i18n_create_zone_by_entity(pimcore_document(16), 'en') %}
{{ dump(document_zone) }}
{{ dump(document_zone.linkedLanguages) }}
<hr>
<h4>I18n Zone Look-Up (object)</h4>
{% set object_zone = i18n_create_zone_by_entity(pimcore_object(16), 'en', { attributes: {_route: 'test_route', object_id: 16 } }) %}
{{ dump(object_zone) }}
{{ dump(object_zone.linkedLanguages) }}
```

```php
<?php

use I18nBundle\Manager\ZoneManager;

class ExampleService
{
    protected ZoneManager $zoneManager;

    public function __construct(ZoneManager $zoneManager)
    {
        $this->zoneManager = $zoneManager;
    }

    public function build(Pimcore\Document $document, string $locale, array $routeParams)
    {
       $zone = $this->zoneManager->buildZoneByEntity($document, $locale, $routeParams);
       
       dump($zone);
    }
}
```