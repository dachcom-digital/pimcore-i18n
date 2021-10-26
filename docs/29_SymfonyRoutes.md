# Symfony Routes

### Routing
First you need to create a valid route 

```yaml
# config/routes.yaml
i18n_symfony_route:
    path:
        en: /en/i18n/my-symfony-route
        de: /de/i18n/meine-symfony-route
        de_CH: /de-ch/i18n/meine-symfony-route-guetzli
    defaults: {
        _i18n: true,
        _controller: App\Controller\DefaultController::symfonyRouteAction
    }
```

Then need to register an alternate listener:
```yaml
App\EventListener\I18nRoutesAlternateListener:
    autowire: true
    tags:
        - { name: kernel.event_subscriber }
```

Now implement the event listener itself:
```php
<?php

namespace App\EventListener;

use I18nBundle\Event\AlternateDynamicRouteEvent;
use Pimcore\Model\DataObject;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class I18nRoutesAlternateListener implements EventSubscriberInterface
{

    /**
    *   ATTENTION!
    *   Do not rely on request stack in this event listener!
    *   This event can be called at any state during different states
    *   Always check/pass your dynamic data via attributes! 
    */
    
    
     public static function getSubscribedEvents(): array
    {
        return [
            I18nEvents::PATH_ALTERNATE_SYMFONY_ROUTE     => 'checkSymfonyRouteAlternate',
        ];
    }
        
    public function checkSymfonyRouteAlternate(AlternateDynamicRouteEvent $event): void
    {
        $attributes = $event->getAttributes();
        $route = $attributes['_route'] ?? null;
        $routes = [];

        if ($route !== 'i18n_symfony_route') {
            return;
        }

        foreach ($event->getI18nList() as $index => $i18nElement) {
            $routes[$index] = [
                'name' => 'i18n_symfony_route',
                'params' => [
                    '_locale' => $i18nElement['locale']
                ]
            ];
        }

        $event->setRoutes($routes);
    }
}
```

## Creating Symfony in Twig 
Nothing special here. Just create your url like you know it from the twig standard:

```twig
{{ url('i18n_symfony_route', {'_locale': app.request.locale}) }}
```