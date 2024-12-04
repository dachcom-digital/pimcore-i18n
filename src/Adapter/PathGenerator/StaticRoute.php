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
use I18nBundle\I18nEvents;
use I18nBundle\Model\RouteItem\AlternateRouteItemInterface;
use I18nBundle\Model\RouteItem\RouteItemInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class StaticRoute extends DynamicRoute
{
    public function __construct(
        RouterInterface $router,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->router = $router;
        $this->eventDispatcher = $eventDispatcher;
    }

    public function configureOptions(OptionsResolver $options): void
    {
        $options
            ->setDefaults(['_route' => null])
            ->setRequired(['_route'])
            ->setAllowedTypes('_route', ['null', 'string']);
    }

    public function getUrls(I18nContextInterface $i18nContext, bool $onlyShowRootLanguages = false): array
    {
        return $this->buildAlternateRoutesStack($i18nContext, RouteItemInterface::STATIC_ROUTE, I18nEvents::PATH_ALTERNATE_STATIC_ROUTE);
    }

    protected function generateLink(AlternateRouteItemInterface $routeItem): string
    {
        $routeParameters = $this->alternateRouteItemTransformer->reverseTransformToArray($routeItem, ['type' => RouteItemInterface::STATIC_ROUTE]);

        if ($routeItem->getRouteName() === null && $routeItem->getEntity() === null) {
            throw new \Exception('cannot create static route url. object or route name is missing');
        }

        return $this->router->generate($routeItem->getRouteName() ?? '', $routeParameters, UrlGeneratorInterface::ABSOLUTE_URL);
    }
}
