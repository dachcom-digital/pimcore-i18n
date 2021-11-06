<?php

namespace I18nBundle\Adapter\PathGenerator;

use I18nBundle\Event\AlternateDynamicRouteEvent;
use I18nBundle\Model\I18nZoneInterface;
use I18nBundle\Model\RouteItem\AlternateRouteItemInterface;

abstract class DynamicRoute extends AbstractPathGenerator
{
    abstract protected function generateLink(AlternateRouteItemInterface $routeItem): string;

    protected function buildAlternateRoutesStack(I18nZoneInterface $zone, string $type, string $eventName): array
    {
        $alternateRouteItems = [];
        $routes = [];

        //create custom list for event ($i18nList) - do not include all the zone config stuff.
        foreach ($zone->getSites(true) as $zoneSite) {
            if (!empty($zoneSite->getLanguageIso())) {
                $alternateRouteItems[] = $this->alternateRouteItemTransformer->transform(
                    $zone->getRouteItem(),
                    [
                        'type'              => $type,
                        'zoneSite'          => $zoneSite,
                        'useZoneSiteLocale' => true
                    ]
                );
            }
        }

        $event = new AlternateDynamicRouteEvent($type, $alternateRouteItems, $zone->getRouteItem());

        $this->eventDispatcher->dispatch($event, $eventName);

        foreach ($event->getAlternateRouteItems() as $alternateRouteItem) {

            if ($alternateRouteItem->isValidAlternateRoute() === false) {
                continue;
            }

            $alternateUrl = $this->generateLink($alternateRouteItem);

            if ($alternateUrl === null) {
                continue;
            }

            $routes[] = [
                'languageIso'      => $alternateRouteItem->getZoneSite()->getLanguageIso(),
                'countryIso'       => $alternateRouteItem->getZoneSite()->getCountryIso(),
                'locale'           => $alternateRouteItem->getZoneSite()->getLocale(),
                'hrefLang'         => $alternateRouteItem->getZoneSite()->getHrefLang(),
                'localeUrlMapping' => $alternateRouteItem->getZoneSite()->getLocaleUrlMapping(),
                'url'              => $alternateUrl
            ];
        }

        return $routes;

    }
}

