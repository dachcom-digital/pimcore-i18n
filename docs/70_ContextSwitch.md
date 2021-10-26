# Context Switch Event
This Event will help you to manage additional tasks if the zone/language/country gets changed.
This feature is disabled by default, so you need to enable it first if you need it:

```yaml
i18n:
    enable_context_switch_detector: true
```

## Use Case Example
If you have a complex e-commerce environment for example, you may want to check the cart against new prices, stock and availability of products if the user changes the country and/or the language.

## Important Stuff to know
- ContextSwitch only works in **same domain levels**. Since there is no way for simple cross-domain session ids, the zone switch will be sort of useless most of the time. 
- The ContextSwitchEvent **ignores** ajax request. If your requesting data via ajax in a different language / country, no event will be triggered!

## Limitation
This event is disabled if the pimcore full page cache feature is enable to prevent session interventions.

***

## Implementation

Create a Service:

```yaml
# config/services.yaml
services:
    App\EventListener\I18nContextSwitchListener:
        public: false
        tags:
            - { name: kernel.event_subscriber }
```

Create a Listener:

```php
<?php

namespace App\EventListener;

use I18nBundle\I18nEvents;
use I18nBundle\Event\ContextSwitchEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class I18nContextSwitchListener implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            I18nEvents::CONTEXT_SWITCH => 'checkContext'
        ];
    }
    
    public function checkContext(ContextSwitchEvent $event): void
    {
        if($event->zoneHasSwitched()) {
            \Pimcore\Logger::log(
                sprintf('switch zone: %s => %s',
                    $event->zoneSwitchedFrom(),
                    $event->zoneSwitchedTo()
                )
            );
        }

        if($event->countryHasSwitched()) {
            \Pimcore\Logger::log(
                sprintf('switch country: %s => %s',
                    $event->countrySwitchedFrom(),
                    $event->countrySwitchedTo()
                )
            );
        }

        if($event->languageHasSwitched()) {
            \Pimcore\Logger::log(
                sprintf('switch lang: %s => %s',
                    $event->languageSwitchedFrom(),
                    $event->languageSwitchedTo()
                )
            );
        }
    }
}
```