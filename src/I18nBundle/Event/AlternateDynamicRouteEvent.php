<?php

namespace I18nBundle\Event;

use I18nBundle\Model\RouteItem\AlternateRouteItemInterface;
use I18nBundle\Model\RouteItem\RouteItemInterface;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Contracts\EventDispatcher\Event;

class AlternateDynamicRouteEvent extends Event
{
    protected string $type;
    protected array $alternateRouteItems = [];
    protected RouteItemInterface $currentRouteItem;

    public function __construct(
        string $type,
        array $alternateRouteItems,
        RouteItemInterface $routeItem
    ) {
        $this->type = $type;
        $this->alternateRouteItems = $alternateRouteItems;
        $this->currentRouteItem = $routeItem;
    }

    /**
     * @deprecated since 4.0.0 and will be removed with 5.0.0, this is not required anymore
     */
    public function setRoutes(array $routes): void
    {
        // do nothing.
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function isCurrentRouteHeadless(): bool
    {
        return $this->currentRouteItem->isHeadless() === true;
    }

    public function getCurrentRouteName(): string
    {
        return $this->currentRouteItem->getRouteName();
    }

    /**
     * @return array<int, AlternateRouteItemInterface>
     *
     * @deprecated since 4.0.0 and will be removed with 5.0.0, use getAlternateRouteItems() instead
     */
    public function getI18nList(): array
    {
        return $this->alternateRouteItems;
    }

    /**
     * @return array<int, AlternateRouteItemInterface>
     */
    public function getAlternateRouteItems(): array
    {
        return $this->alternateRouteItems;
    }

    public function getCurrentLocale(): ?string
    {
        return $this->currentRouteItem->getLocaleFragment();
    }

    public function getCurrentRouteAttributes(): ParameterBag
    {
        return $this->currentRouteItem->getRouteAttributesBag();
    }

    public function getCurrentRouteParameters(): ParameterBag
    {
        return $this->currentRouteItem->getRouteParametersBag();
    }
}
