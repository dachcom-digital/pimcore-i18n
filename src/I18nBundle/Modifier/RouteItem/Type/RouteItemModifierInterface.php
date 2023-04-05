<?php

namespace I18nBundle\Modifier\RouteItem\Type;

use I18nBundle\Model\RouteItem\RouteItemInterface;
use Symfony\Component\HttpFoundation\Request;

interface RouteItemModifierInterface
{
    public function supportParameters(string $type, RouteItemInterface $routeItem, array $parameters, array $context): bool;

    public function supportRequest(string $type, RouteItemInterface $routeItem, Request $request, array $context): bool;

    public function modifyByParameters(RouteItemInterface $routeItem, array $parameters, array $context): void;

    public function modifyByRequest(RouteItemInterface $routeItem, Request $request, array $context): void;
}
