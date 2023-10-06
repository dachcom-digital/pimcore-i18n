# Static Routes
The I18nBundle will help you to create translatable static routes and will also help you to generate valid alternate links.

## Generating Routes in current request
To create static route paths/urls in **current request** via Twig or PHP API
you may want to use the `_i18n` parameter builder:

### Twig
```twig
{# relative [by static route name] #}
{{ dump( i18n_static_route('my_static_route', { news: 'my-attribute' }, true) ) }}
 
{# absolute [by static route name] #}
{{ dump( i18n_static_route('my_static_route', { news: 'my-attribute' }, true) ) }}

{# relative [object with link generator] #}
{{ dump( i18n_entity_route(pimcore_object(20), {}, false) ) }}
 
{# absolute [object with link generator] #}
{{ dump( i18n_entity_route(pimcore_object(20), {}, true) ) }}
```

### PHP
```php
use I18nBundle\Builder\RouteParameterBuilder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

public function myAction(Request $request) 
{
    // set object
    $object = \Pimcore\Model\DataObject::getById(20);
    
    $parameters = RouteParameterBuilder::buildForEntity($object);

    $linkGeneratorStaticRoute = $this->urlGenerator->generate('', $parameters, UrlGeneratorInterface::ABSOLUTE_URL);
    
    $parameters = RouteParameterBuilder::buildForStaticRoute(
        [
            'news' => 'my-attribute'
        ]
    );

    $instantStaticRoute = $this->urlGenerator->generate('my_static_route', $parameters, UrlGeneratorInterface::ABSOLUTE_URL);
}
```

## Generating Routes in CLI
To create static route paths/urls in **headless** context:

### PHP
```php
use I18nBundle\Builder\RouteParameterBuilder;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

protected function execute(InputInterface $input, OutputInterface $output): int
{
    $object = \Pimcore\Model\DataObject::getById(20);
    
    $parameters = RouteParameterBuilder::buildForEntity(
        $object,
        [
            '_locale' => 'en'
        ],
        [
            'site' => Site::getByDomain('test-domain1.test')
        ]
    );

    $linkGeneratorStaticRoute = $this->urlGenerator->generate('', $parameters, UrlGeneratorInterface::ABSOLUTE_URL);
    
    $parameters = RouteParameterBuilder::buildForStaticRoute(
        [
            '_locale' => 'en',
            'news' => 'my-attribute'
        ],
        [
            'site' => Site::getByDomain('test-domain1.test')
        ]
    );

    $instantStaticRoute = $this->urlGenerator->generate('my_static_route', $parameters, UrlGeneratorInterface::ABSOLUTE_URL);
    
    return 0;
}
```

## Translatable Fragments
Creating translatable static routes are quite a challenge in pimcore. 
We'll show you how to create them based on our [Pimcore News Bundle](https://github.com/dachcom-digital/pimcore-news).

### Routing
First you need to create a valid route. 

| Pattern | Reverse |
|---------|-------------|
| `/([a-zA-Z0-9-_]*)\/(?:news\|artikel)\/(.*?)$/` | `/{%_locale}/@news/%entry` |

This static route allows structures like:

- `domain.com/en-us/news/my-news`
- `domain.com/en_US/news/my-news`
- `domain.com/news/my-news`
- `domain.com/artikel/mein-neuer-artikel`

**Important:** This function is only available if you're having a `%_locale` element in your reverse section. Without it, it can't get translated!

### Translation Pattern
In the reverse field you may have recognized the `@news` element. This element will get translated during the url generation.
Please be sure that all translation keys are also available in the pattern section (`(?:news\|artikel|xy)` for example)
Now we'll add some configuration to translate this fragment:

```yaml
# config/packages/i18n.yaml
i18n:

    locale_adapter: system
    
    # you also need to add them to every zone, if you have any
    translations:
        -
            key: 'news'
            values:
                de: 'artikel'
                de_CH: 'artikel'
                en: 'news'
                en_AU: 'news'
                fr: 'nouvelles'

# example static route
pimcore:
    staticroutes:
        definitions:
            60f02a5d-eb8f-4fb2-859a-0a3c77b472f9:
                name: my_static_route
                pattern: '/([a-zA-Z0-9-_]*)\/(?:%i18n.route.translations.news%)\/(.*?)$/' ## returns (artikel|news|nouvelles)
                reverse: '/{%%_locale}/@news/%%entry'
                controller: App\Controller\DefaultController::staticRouteAction
                variables: '_locale,entry'
                defaults: null
                siteId: null
                methods: null
                priority: 1
                creationDate: 1634472684
                modificationDate: 1634472684
```

> **Info:** Why not using the default pimcore translation service you may asking? We also thought about that. 
> The reason is: It's quite dangerous to add routing translations to the backend since they could get changed by customers / translation services very easily which could lead to serious SEO problems.

## Optional Locale in Url
The I18nBundle allows you to have domains without a locale fragment in url. For Example:
- `domain.com/about-us`
- `domain.com/news`

To achieve that you just set the language property to the `domain.com` document which is also a pimcore site.
That was easy! Now we need to check the static routes again - because the pattern always requires the locale part.
Pimcore allows [optional placeholders](https://pimcore.com/docs/5.0.x/MVC/Routing_and_URLs/Custom_Routes.html#page_Building_URLs_based_on_Custom_Routes) so instead of `%_locale` just add `{%_locale}` to your reverse element.
If no locale has been found in your request url the fragment now gets excluded.

## Alternate Links Generator
Then need to register an alternate listener and its corresponding service.

```html
<link href="https://www.domain.com/de/artikel/news-mit-headline" rel="alternate" hreflang="de" />
<link href="https://www.domain.com/de-ch/artikel/schweizer-headline" rel="alternate" hreflang="de-ch" />
<link href="https://www.domain.com/en-us/news/englisch-australia-news" rel="alternate" hreflang="en-au" />
<link href="https://www.domain.com/nouvelles/titre-francais" rel="alternate" hreflang="fr" />
```

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
            I18nEvents::PATH_ALTERNATE_STATIC_ROUTE => 'checkStaticRouteAlternate'
        ];
    }
        
    public function checkStaticRouteAlternate(AlternateDynamicRouteEvent $event): void
    {
        // be careful here! Alternate requests are able to resolve two states:
        // I. non-headless: generate alternate links for a given request (main request) which will pass the request attributes
        //   => you'll find your mapped data in $event->getCurrentRouteAttributes()
        // II. headless: requested by a standalone zone entity which passes will pass route parameters
        //   => you'll find your mapped data in $event->getCurrentRouteParameters()

        $attributes = $event->isCurrentRouteHeadless() ? $event->getCurrentRouteParameters() : $event->getCurrentRouteAttributes();
        
        // depending on given route, you may want to build different alternate route items
        if($event->getCurrentRouteName() !== 'test_route') {
            return;
        }

        $entryId = $attributes->get('entry');
        $news = DataObject\NewsEntry::getByLocalizedfields('detailUrl', $entryId, $event->getCurrentLocale(), ['limit' => 1]);

        if (!$news instanceof DataObject\NewsEntry) {
            return;
        }

        $locale = $attributes->get('_locale');

        foreach ($event->getAlternateRouteItems() as $alternateRouteItem) {

            $newsName = $news->getName($locale);

            if (empty($newsName)) {
                continue;
            }
            
            //  Strategy I. (recommended) ############################
            //  Use link generator, only pass the object.
            //  ######################################################
            $alternateRouteItem->setEntity($news);
            
            //  Strategy II. #########################################
            //  Default static route generation (no link generator)
            //  ######################################################

            //set the static route name (in this case it depends on the entry type.
            $alternateRouteItem->setRouteName($news->getEntryType() === 'news' ? 'news_detail' : 'blog_detail');
            $alternateRouteItem->getRouteParametersBag()->add([
                'entry' => $news->getDetailUrl($locale)
            ]);
          
        }
    }
}
```

## Naming Convention in Country Context
The static route configuration above will generate the locale fragment based on the `%_locale` element. 
So your i18n urls should look like this, otherwise symfony will not recognize the locale:
- `www.domain.com/de/artikel/mein-artikel`
- `www.domain.com/de_CH/artikel/mein-artikel`
- `www.domain.com/en_US/news/my-news`

This looks quite ugly. We want some nice looking urls:
- `www.domain.com/de/artikel/mein-artikel`
- `www.domain.com/de-ch/artikel/mein-artikel`
- `www.domain.com/en-us/news/my-news`

Yea - that's also possible. Just create your [country element](27_Countries.md) like described and set the document key to `en-us` instead of `en_US` for example.
This Bundle will automatically transform your static routes locale fragment into valid ones.

> **Note:** Of course it's still possible to use iso code formatted url structures if you really want to do that. :)