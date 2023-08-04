<?php

namespace I18nBundle\Model\RouteItem;

use Symfony\Component\HttpFoundation\ParameterBag;

class LinkGeneratorRouteItem implements LinkGeneratorRouteItemInterface
{
    protected string $type;
    protected bool $headless;
    protected ?string $routeName = null;
    protected ParameterBag $routeParameters;
    protected ParameterBag $routeAttributes;
    protected ParameterBag $routeContext;

    public function __construct(string $routeName, bool $headless)
    {
        $this->type = RouteItemInterface::STATIC_ROUTE;
        $this->routeName = $routeName;
        $this->headless = $headless;
        $this->routeParameters = new ParameterBag();
        $this->routeAttributes = new ParameterBag();
        $this->routeContext = new ParameterBag();
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function isHeadless(): bool
    {
        return $this->headless;
    }

    public function getRouteParametersBag(): ParameterBag
    {
        return $this->routeParameters;
    }

    public function getRouteParameters(): array
    {
        return $this->routeParameters->all();
    }

    public function getRouteAttributesBag(): ParameterBag
    {
        return $this->routeAttributes;
    }

    public function getRouteAttributes(): array
    {
        return $this->routeAttributes->all();
    }

    public function getRouteContextBag(): ParameterBag
    {
        return $this->routeContext;
    }

    public function getRouteContext(): array
    {
        return $this->routeContext->all();
    }

    public function hasLocaleFragment(): bool
    {
        return $this->routeParameters->has('_locale');
    }

    public function getLocaleFragment(): ?string
    {
        return $this->routeParameters->get('_locale');
    }

    public function getRouteName(): ?string
    {
        return $this->routeName;
    }
}