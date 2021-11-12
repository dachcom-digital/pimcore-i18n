<?php

namespace I18nBundle\Builder;

use I18nBundle\Definitions;
use I18nBundle\Model\RouteItem\RouteItemInterface;
use Pimcore\Http\Request\Resolver\SiteResolver;
use Pimcore\Model\DataObject\AbstractObject;
use Pimcore\Model\Document;
use Pimcore\Model\Element\ElementInterface;
use Pimcore\Tool;
use Pimcore\Tool\Frontend;
use Symfony\Cmf\Bundle\RoutingBundle\Routing\DynamicRouter;
use Symfony\Component\HttpFoundation\Request;

class RouteParameterBuilder
{
    public static function buildForEntity(ElementInterface $element, array $routeParameter, array $context = []): array
    {
        return self::buildRouteParams(null, $routeParameter, $context, null, $element);
    }

    public static function buildForEntityWithRequest(ElementInterface $element, array $routeParameter, Request $request): array
    {
        return self::buildRouteParams(null, $routeParameter, [], $request, $element);
    }

    public static function buildForStaticRoute(array $routeParameter, array $context = []): array
    {
        return self::buildRouteParams(RouteItemInterface::STATIC_ROUTE, $routeParameter, $context);
    }

    public static function buildForStaticRouteWithRequest(array $routeParameter, Request $request): array
    {
        return self::buildRouteParams(RouteItemInterface::STATIC_ROUTE, $routeParameter, [], $request);
    }

    public static function buildForSymfonyRoute(array $routeParameter, array $context = []): array
    {
        return self::buildRouteParams(RouteItemInterface::SYMFONY_ROUTE, $routeParameter, $context);
    }

    public static function buildForSymfonyRouteWithRequest(array $routeParameter, Request $request): array
    {
        return self::buildRouteParams(RouteItemInterface::SYMFONY_ROUTE, $routeParameter, [], $request);
    }

    private static function buildRouteParams(
        ?string $routeType,
        array $routeParameter,
        array $context,
        ?Request $request = null,
        ?ElementInterface $element = null
    ): array {

        $params = [
            'routeParameters' => $routeParameter,
            'context'         => $context
        ];

        if ($element !== null) {
            if ($element instanceof Document) {
                $routeType = RouteItemInterface::DOCUMENT_ROUTE;
            } elseif ($element instanceof AbstractObject) {
                $routeType = RouteItemInterface::STATIC_ROUTE;
            } else {
                throw new \Exception('Cannot build route parameters for entity "%"', get_class($element));
            }

            $params['entity'] = $element;
        }

        if ($routeType === null) {
            throw new \Exception('Cannot build route parameters because of unknown rout type');
        }

        $params['type'] = $routeType;

        if (!$request instanceof Request) {
            return [Definitions::ATTRIBUTE_I18N_ROUTE_IDENTIFIER => $params];
        }

        if ($routeType === RouteItemInterface::DOCUMENT_ROUTE) {
            return [Definitions::ATTRIBUTE_I18N_ROUTE_IDENTIFIER => $params];
        }

        if ($request->attributes->has(SiteResolver::ATTRIBUTE_SITE)) {
            $params['context']['site'] = $request->attributes->get(SiteResolver::ATTRIBUTE_SITE);
        } elseif (Tool::isFrontendRequestByAdmin($request) && $request->attributes->has(DynamicRouter::CONTENT_KEY)) {
            $params['context']['site'] = Frontend::getSiteForDocument($request->attributes->get(DynamicRouter::CONTENT_KEY));
        }

        if ($request->attributes->has('_locale')) {
            $params['routeParameters']['_locale'] = $request->getLocale();
        }

        return [Definitions::ATTRIBUTE_I18N_ROUTE_IDENTIFIER => $params];
    }

}
