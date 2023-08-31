<?php

namespace I18nBundle\Transformer;

use I18nBundle\Definitions;
use I18nBundle\Model\ZoneSiteInterface;
use I18nBundle\Model\RouteItem\AlternateRouteItem;
use I18nBundle\Model\RouteItem\AlternateRouteItemInterface;
use I18nBundle\Model\RouteItem\LinkGeneratorRouteItemInterface;
use I18nBundle\Model\RouteItem\RouteItemInterface;

class AlternateRouteItemTransformer implements TransformerInterface
{
    public function transform(RouteItemInterface $routeItem, array $context = []): AlternateRouteItemInterface
    {
        $type = $context['type'] ?? '';
        /** @var ZoneSiteInterface $zoneSite */
        $zoneSite = $context['zoneSite'];

        $alternateRouteItem = new AlternateRouteItem($type, $routeItem->isHeadless(), $zoneSite);
        $alternateRouteItem->getRouteAttributesBag()->add(array_filter($routeItem->getRouteAttributes(), static function ($key) {
            return in_array($key, ['_route', '_controller'], true);
        }, ARRAY_FILTER_USE_KEY));

        $alternateRouteItem->getRouteParametersBag()->add($routeItem->getRouteParameters());
        $alternateRouteItem->getRouteContextBag()->add($routeItem->getRouteContext());

        $alternateRouteItem->getRouteParametersBag()->set('_locale', $zoneSite->getLocale());
        $alternateRouteItem->getRouteContextBag()->set('site', $zoneSite->getPimcoreSite());

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