<?php

namespace I18nBundle\Adapter\PathGenerator;

use I18nBundle\I18nEvents;
use I18nBundle\Model\I18nZoneInterface;
use I18nBundle\Model\RouteItem\AlternateRouteItemInterface;
use I18nBundle\Model\RouteItem\RouteItemInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class Symfony extends DynamicRoute
{
    protected RouterInterface $router;
    protected EventDispatcherInterface $eventDispatcher;

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

    public function getUrls(I18nZoneInterface $zone, bool $onlyShowRootLanguages = false): array
    {
        return $this->buildAlternateRoutesStack($zone, RouteItemInterface::SYMFONY_ROUTE, I18nEvents::PATH_ALTERNATE_SYMFONY_ROUTE);
    }

    protected function generateLink(AlternateRouteItemInterface $routeItem): string
    {
        $routeParameters = $this->alternateRouteItemTransformer->reverseTransformToArray($routeItem, ['type' => RouteItemInterface::SYMFONY_ROUTE]);

        return $this->router->generate($routeItem->getRouteName(), $routeParameters, UrlGeneratorInterface::ABSOLUTE_URL);
    }
}
