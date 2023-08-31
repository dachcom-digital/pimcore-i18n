<?php

namespace I18nBundle\Model\RouteItem;

use Pimcore\Model\Element\ElementInterface;
use Symfony\Component\HttpFoundation\ParameterBag;

abstract class BaseRouteItem
{
    protected string $type;
    protected bool $headless;
    protected ?string $routeName = null;
    protected ?ElementInterface $entity = null;
    protected ParameterBag $routeParameters;
    protected ParameterBag $routeAttributes;
    protected ParameterBag $routeContext;

    public function __construct(string $type, bool $headless)
    {
        if (!in_array($type, [
            RouteItemInterface::DOCUMENT_ROUTE,
            RouteItemInterface::SYMFONY_ROUTE,
            RouteItemInterface::STATIC_ROUTE,
        ], true)) {
            throw new \Exception(sprintf('Invalid RouteItem type "%s"', $type));
        }

        $this->type = $type;
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

    public function getLocaleFragment(): ?string
    {
        return $this->routeParameters->get('_locale');
    }

    public function hasLocaleFragment(): bool
    {
        return $this->routeParameters->has('_locale');
    }

    public function isFrontendRequestByAdmin(): bool
    {
        return $this->routeContext->has('isFrontendRequestByAdmin') && $this->routeContext->get('isFrontendRequestByAdmin') === true;
    }

    public function hasEntity(): bool
    {
        return $this->entity instanceof ElementInterface;
    }

    public function getEntity(): ?ElementInterface
    {
        return $this->entity;
    }

    public function setEntity(?ElementInterface $entity): void
    {
        $this->entity = $entity;
    }

    public function hasRouteName(): bool
    {
        return $this->routeName !== null;
    }

    public function getRouteName(): ?string
    {
        return $this->routeName;
    }

    public function setRouteName(?string $routeName): void
    {
        $this->routeName = $routeName;
    }
}
