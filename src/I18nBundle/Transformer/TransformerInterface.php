<?php

namespace I18nBundle\Transformer;

use I18nBundle\Model\RouteItem\RouteItemInterface;

interface TransformerInterface
{
    public function transform(RouteItemInterface $routeItem, array $context = []): mixed;

    public function reverseTransform(mixed $transformedRouteItem, array $context = []): RouteItemInterface;

    public function reverseTransformToArray(mixed $transformedRouteItem, array $context = []): array;
}