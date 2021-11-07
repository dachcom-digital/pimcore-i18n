<?php

namespace I18nBundle\Adapter\PathGenerator;

use I18nBundle\Context\I18nContextInterface;
use I18nBundle\Event\AlternateDynamicRouteEvent;
use I18nBundle\Model\RouteItem\AlternateRouteItemInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

abstract class DynamicRoute extends AbstractPathGenerator
{
    protected RouterInterface $router;
    protected EventDispatcherInterface $eventDispatcher;

    abstract protected function generateLink(AlternateRouteItemInterface $routeItem): string;

    protected function buildAlternateRoutesStack(I18nContextInterface $i18nContext, string $type, string $eventName): array
    {
        $alternateRouteItems = [];
        $routes = [];

        //create custom list for event ($i18nList) - do not include all the zone config stuff.
        foreach ($i18nContext->getZone()->getSites(true) as $zoneSite) {
            if (!empty($zoneSite->getLanguageIso())) {
                $alternateRouteItems[] = $this->alternateRouteItemTransformer->transform(
                    $i18nContext->getRouteItem(),
                    [
                        'type'              => $type,
                        'zoneSite'          => $zoneSite,
                        'useZoneSiteLocale' => true
                    ]
                );
            }
        }

        $event = new AlternateDynamicRouteEvent($type, $alternateRouteItems, $i18nContext->getRouteItem());

        $this->eventDispatcher->dispatch($event, $eventName);

        foreach ($event->getAlternateRouteItems() as $alternateRouteItem) {

            if ($alternateRouteItem->isValidAlternateRoute() === false) {
                continue;
            }

            $routes[] = [
                'languageIso'      => $alternateRouteItem->getZoneSite()->getLanguageIso(),
                'countryIso'       => $alternateRouteItem->getZoneSite()->getCountryIso(),
                'locale'           => $alternateRouteItem->getZoneSite()->getLocale(),
                'hrefLang'         => $alternateRouteItem->getZoneSite()->getHrefLang(),
                'localeUrlMapping' => $alternateRouteItem->getZoneSite()->getLocaleUrlMapping(),
                'url'              => $this->generateLink($alternateRouteItem)
            ];
        }

        return $routes;
    }
}

