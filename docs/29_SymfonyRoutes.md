# Symfony Routes

### Routing
First you need to create a valid route. Read more about the symfony route definitions [here](./1_I18n.md#symfony-routes).

```yaml
# config/routes.yaml
i18n:
    translations:
        - key: 'mySymfonyRouteKey'
          values:
              de: 'meine-symfony-route'
              en: 'my-symfony-route'
              
i18n_symfony_route:
    path: /{_locale}/i18n/{matching_route_key}
    defaults:
        _i18n:
            translation_keys:
                matching_route_key: mySymfonyRouteKey
        _controller: App\Controller\DefaultController::symfonyRouteAction
    requirements:
        matching_route_key: '(%i18n.route.translations.mySymfonyRouteKey%)' ## returns (meine-symfony-route|my-symfony-route)
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
            I18nEvents::PATH_ALTERNATE_SYMFONY_ROUTE => 'checkSymfonyRouteAlternate',
        ];
    }
        
    public function checkSymfonyRouteAlternate(AlternateDynamicRouteEvent $event): void
    {
        if($event->getCurrentRouteName() !== 'i18n_symfony_route') {
            return;
        }
                
        foreach ($event->getAlternateRouteItems() as $alternateRouteItem) {
            $alternateRouteItem->setRouteName('i18n_symfony_route');
        }
    }
}
```

## Creating Symfony in Twig 
Just create your url like you know it from the twig standard and pass your parameters via the `_18n` flag:
I18n will transform your locale fragment, if necessary:

```twig
{{ url('i18n_symfony_route', { _i18n: { routeParameters: { _locale: app.request.locale } } }) }}
```