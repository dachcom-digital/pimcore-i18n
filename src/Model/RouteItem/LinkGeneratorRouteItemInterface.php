<?php

namespace I18nBundle\Model\RouteItem;

use Symfony\Component\HttpFoundation\ParameterBag;

interface LinkGeneratorRouteItemInterface
{
    public function getType(): string;

    public function isHeadless(): bool;

    public function getRouteParametersBag(): ParameterBag;

    public function getRouteParameters(): array;

    public function getRouteAttributesBag(): ParameterBag;

    public function getRouteAttributes(): array;

    public function getRouteContextBag(): ParameterBag;

    public function getRouteContext(): array;

    public function hasLocaleFragment(): bool;

    public function getLocaleFragment(): ?string;

    public function getRouteName(): ?string;
}
