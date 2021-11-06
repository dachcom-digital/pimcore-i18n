<?php

namespace I18nBundle\Twig\Extension;

use I18nBundle\Http\RouteItemResolverInterface;
use I18nBundle\Manager\RouteItemManager;
use I18nBundle\Model\I18nZoneInterface;
use I18nBundle\Model\RouteItem\RouteItemInterface;
use Pimcore\Model\DataObject\AbstractObject;
use Pimcore\Model\Document;
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Model\Site;
use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class I18nExtension extends AbstractExtension
{
    protected RequestStack $requestStack;
    protected RouteItemResolverInterface $routeItemResolver;
    protected RouteItemManager $routeItemManager;

    public function __construct(
        RequestStack $requestStack,
        RouteItemResolverInterface $routeItemResolver,
        RouteItemManager $routeItemManager
    ) {
        $this->requestStack = $requestStack;
        $this->routeItemResolver = $routeItemResolver;
        $this->routeItemManager = $routeItemManager;
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('i18n_zone', [$this, 'getI18nZone']),
            new TwigFunction('i18n_create_zone_by_entity', [$this, 'createI18nZoneByEntity']),
            new TwigFunction('i18n_create_zone_by_static_route', [$this, 'createI18nZoneByStaticRoute']),
            new TwigFunction('i18n_create_zone_by_symfony_route', [$this, 'createI18nZoneBySymfonyRoute']),
        ];
    }

    public function getI18nZone(): ?I18nZoneInterface
    {
        return $this->routeItemResolver->getRouteItem($this->requestStack->getCurrentRequest())->getI18nZone();
    }

    public function createI18nZoneByEntity(ElementInterface $entity, array $routeParameter = [], ?Site $site = null): I18nZoneInterface
    {
        $routeItemParameters = [
            'routeParameters' => $routeParameter,
            'entity'          => $entity,
            'context'         => [
                'site' => $site
            ]
        ];

        if ($entity instanceof Document) {
            $routeItemParameters['type'] = RouteItemInterface::DOCUMENT_ROUTE;
        } elseif ($entity instanceof AbstractObject) {
            $routeItemParameters['type'] = RouteItemInterface::STATIC_ROUTE;
        } else {
            throw new \Exception('Cannot build zone for entity "%"', get_class($entity));
        }

        return $this->routeItemManager->buildRouteItemByParameters($routeItemParameters)->getI18nZone();
    }

    public function createI18nZoneByStaticRoute(string $route, array $routeParameter = [], ?Site $site = null): I18nZoneInterface
    {
        $routeItemParameters = [
            'type'            => RouteItemInterface::STATIC_ROUTE,
            'routeParameters' => $routeParameter,
            'routeName'       => $route,
            'context'         => [
                'site' => $site
            ]
        ];

        return $this->routeItemManager->buildRouteItemByParameters($routeItemParameters)->getI18nZone();
    }

    public function createI18nZoneBySymfonyRoute(string $route, array $routeParameter = [], ?Site $site = null): I18nZoneInterface
    {
        $routeItemParameters = [
            'type'            => RouteItemInterface::SYMFONY_ROUTE,
            'routeParameters' => $routeParameter,
            'routeName'       => $route,
            'context'         => [
                'site' => $site
            ]
        ];

        return $this->routeItemManager->buildRouteItemByParameters($routeItemParameters)->getI18nZone();
    }
}
