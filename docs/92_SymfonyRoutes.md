# Symfony Routes

### Routing
First you need to create a valid route. 
Read more about the symfony route definitions [here](./1_I18n.md#symfony-routes).

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
    controller: App\Controller\DefaultController::symfonyRouteAction
    defaults:
        _i18n:
            translation_keys:
                matching_route_key: mySymfonyRouteKey
    requirements:
        matching_route_key: '(%i18n.route.translations.mySymfonyRouteKey%)' ## returns (meine-symfony-route|my-symfony-route)
```

## Generating Routes in current request
To create symfony paths/urls in **current request** via Twig or PHP API
you may want to use the `_i18n` parameter builder:

### Twig
```twig
{# relative #}
{{ dump( i18n_symfony_route('i18n_symfony_route', {foo: bar}, false) ) }}

{# absolute #}
{{ dump( i18n_symfony_route('i18n_symfony_route', {foo: bar}, true) ) }}
```

### PHP
```php
use I18nBundle\Builder\RouteParameterBuilder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

public function myAction(Request $request) 
{
    $parameters = RouteParameterBuilder::buildForSymfonyRoute(
        [
            'foo' => 'bar'
        ]
    );

    $symfonyRoute = $this->urlGenerator->generate('i18n_symfony_route', $parameters, UrlGeneratorInterface::ABSOLUTE_URL);
}
```

## Generating Routes in CLI
To create symfony paths/urls in **headless** context:

### PHP
```php
use I18nBundle\Builder\RouteParameterBuilder;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

protected function execute(InputInterface $input, OutputInterface $output): int
{
    $parameters = RouteParameterBuilder::buildForSymfonyRoute(
        [
            'foo' => 'bar'
        ],
        [
            'site' => Site::getByDomain('test-domain1.test')
        ]
    );

    $symfonyRoute = $this->urlGenerator->generate('i18n_symfony_route', $parameters, UrlGeneratorInterface::ABSOLUTE_URL);
    
    return 0;
}
```

## Alternate Links
Then need to register an alternate listener and its corresponding service.

```yaml
App\EventListener\I18nRoutesAlternateListener:
    autowire: true
    tags:
        - { name: kernel.event_subscriber }
```

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
