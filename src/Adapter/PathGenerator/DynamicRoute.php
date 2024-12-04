<?php

/*
 * This source file is available under two different licenses:
 *   - GNU General Public License version 3 (GPLv3)
 *   - DACHCOM Commercial License (DCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) DACHCOM.DIGITAL AG (https://www.dachcom-digital.com)
 * @license    GPLv3 and DCL
 */

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

        foreach ($i18nContext->getZone()->getSites(true) as $zoneSite) {
            if (!empty($zoneSite->getLanguageIso())) {
                $alternateRouteItems[] = $this->alternateRouteItemTransformer->transform(
                    $i18nContext->getRouteItem(),
                    [
                        'type'     => $type,
                        'zoneSite' => $zoneSite
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
