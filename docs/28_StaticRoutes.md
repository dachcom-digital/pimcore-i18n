# Static Routes
The I18nBundle will help you to create translatable static routes and will also help you to generate valid alternate links.

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
# app/config/config.yml
i18n:

    mode: country
    language_adapter: system
    country_adapter: system
    
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
```

> **Info:** Why not using the default pimcore translation service you may asking? We also thought about that. 
> The reason is: It's quite dangerous to add routing translations to the backend since they could get changed by customers / translation services very easily which could lead to serious SEO problems.

## Optional Locale in Url
The I18nBundle allows you to have domains without a locale fragment in url. For Example:
- `domain.com/about-us`
- `domain.com/news`

To achieve that you just set the language property to the `domain.com` document which is also a pimcore site. Easy! But for static routes, the pattern always requires the locale part.
Pimcore allows [optional placeholders](https://pimcore.com/docs/5.0.x/MVC/Routing_and_URLs/Custom_Routes.html#page_Building_URLs_based_on_Custom_Routes) so instead of `%_locale` just add `{%_locale}` to your reverse element.

## href-lang Generator
Now let's create a event listener to generate valid alternate links for our news entries:

```html
<link href="https://www.domain.com/de/artikel/news-mit-headline" rel="alternate" hreflang="de" />
<link href="https://www.domain.com/de-ch/artikel/schweizer-headline" rel="alternate" hreflang="de-ch" />
<link href="https://www.domain.com/en-us/news/englisch-australia-news" rel="alternate" hreflang="en-au" />
<link href="https://www.domain.com/nouvelles/titre-francais" rel="alternate" hreflang="fr" />
```

First you need to register a service:
```yaml
AppBundle\EventListener\StaticRoutesAlternateListener:
    autowire: true
    public: false
    tags:
        - { name: kernel.event_listener, event: i18n.path.static_route.alternate, method: checkAlternate }
```

Now implement the event listener itself:
```php
<?php

namespace AppBundle\EventListener;

use I18nBundle\Event\AlternateStaticRouteEvent;
use Pimcore\Model\DataObject;

class StaticRoutesAlternateListener
{
    public function checkAlternate(AlternateStaticRouteEvent $event)
    {
        $route = $event->getCurrentStaticRoute();
        $requestAttributes = $event->getRequestAttributes();
        $routes = [];

        if ($route->getModule() === 'News' &&
            $route->getController() === 'entry' &&
            $route->getAction() === 'detail'
            ) {
                $entryId = $requestAttributes->get('entry');
                $news = DataObject\NewsEntry::getByLocalizedfields('detailUrl', $entryId, $requestAttributes->get('_locale'), ['limit' => 1]);

                if ($news instanceof DataObject\NewsEntry) {
                    foreach ($event->getI18nList() as $index => $i18nElement) {
                        $locale = $i18nElement['locale'];
                        $newsName = $news->getName($locale);

                        if (empty($newsName)) {
                            continue;
                        }
                        
                        //the default static route params
                        // do NOT forget the index value!!
                        $routes[$index] = [
                            'params' => [
                                '_locale' => $locale,
                                'entry'   => $news->getDetailurl($locale),
                            ],
                            //set the static route name (in this case it depends on the entry type.
                            'name'   => $news->getEntryType() === 'news' ? 'news_detail' : 'blog_detail'
                        ];
                    }
                }

            $event->setRoutes($routes);
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

This looks quite ugly, right? We want some nice looking urls:
- `www.domain.com/de/artikel/mein-artikel`
- `www.domain.com/de-ch/artikel/mein-artikel`
- `www.domain.com/en-us/news/my-news`

Yea - that's also possible. Just create your [country element](27_Countries.md) like described and set the document key to `en-us` instead of `en_US` for example.
This Bundle will automatically transform your static routes locale fragment into valid ones.

> **Note:** Of course it's still possible to use iso code formatted url structures if you really want to do that. ;)

## Creating Static Routes in Twig 
Nothing special here. Just create your url like you know it from the twig standard.
I18nBundle will transform your locale fragment, if necessary:

```twig
{{ url('your_static_route', {'_locale': app.request.locale, 'param1': param1}) }}
```