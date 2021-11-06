<?php

namespace I18nBundle\Transformer;

use I18nBundle\Definitions;
use I18nBundle\Model\I18nZoneSiteInterface;
use I18nBundle\Model\RouteItem\AlternateRouteItem;
use I18nBundle\Model\RouteItem\AlternateRouteItemInterface;
use I18nBundle\Model\RouteItem\LinkGeneratorRouteItemInterface;
use I18nBundle\Model\RouteItem\RouteItemInterface;

class AlternateRouteItemTransformer implements TransformerInterface
{
    public function transform(RouteItemInterface $routeItem, array $context = []): AlternateRouteItemInterface
    {
        $type = $context['type'] ?? '';
        /** @var I18nZoneSiteInterface $zoneSite */
        $zoneSite = $context['zoneSite'] ?? '';
        $useZoneSiteLocale = $context['useZoneSiteLocale'] ?? false;

        $alternateRouteItem = new AlternateRouteItem($type, $routeItem->isHeadless(), $zoneSite);
        $alternateRouteItem->getRouteAttributesBag()->add($routeItem->getRouteAttributes());
        $alternateRouteItem->getRouteParametersBag()->add($routeItem->getRouteParameters());
        $alternateRouteItem->getRouteContextBag()->add($routeItem->getRouteContext());

        if ($useZoneSiteLocale === true && $routeItem->hasLocaleFragment()) {
            $alternateRouteItem->getRouteParametersBag()->set('_locale', $zoneSite->getLocale());
        }

        return $alternateRouteItem;

    }

    public function reverseTransform(mixed $transformedRouteItem, array $context = []): RouteItemInterface
    {
        throw new \Exception('Not Supported');
    }

    public function reverseTransformToArray(mixed $transformedRouteItem, array $context = []): array
    {
        if (!$transformedRouteItem instanceof AlternateRouteItemInterface) {
            throw new \Exception(sprintf(
                    'Transformed route item must be instance of "%s", "%s" given.',
                    LinkGeneratorRouteItemInterface::class,
                    get_class($transformedRouteItem)
                )
            );
        }

        return [
            Definitions::ATTRIBUTE_I18N_ROUTE_IDENTIFIER => [
                'type'            => $context['type'] ?? '',
                'entity'          => $transformedRouteItem->getEntity(),
                'routeName'       => $transformedRouteItem->getRouteName(),
                'routeParameters' => $transformedRouteItem->getRouteParameters(),
                'routeAttributes' => $transformedRouteItem->getRouteAttributes(),
                'context'         => $transformedRouteItem->getRouteContext()
            ]
        ];
    }
}