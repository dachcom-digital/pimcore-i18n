<?php

/*
 * This source file is available under two different licenses:
 *   - GNU General Public License version 3 (GPLv3)
 *   - DACHCOM Commercial License (DCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) DACHCOM.DIGITAL AG (https://www.dachcom-digital.com)
 * @license    GPLv3 and DCL
 */

namespace I18nBundle\Builder;

use I18nBundle\Definitions;
use I18nBundle\Model\RouteItem\RouteItemInterface;
use Pimcore\Model\DataObject\AbstractObject;
use Pimcore\Model\Document;
use Pimcore\Model\Element\ElementInterface;
use Symfony\Component\HttpFoundation\Request;

class RouteParameterBuilder
{
    public static function buildForEntity(ElementInterface $element, array $routeParameter, array $context = []): array
    {
        return self::buildRouteParams(null, $routeParameter, $context, $element);
    }

    /**
     * @deprecated use buildForEntity() instead. locale and site will be injected automatically, if given
     */
    public static function buildForEntityWithRequest(ElementInterface $element, array $routeParameter, Request $request): array
    {
        return self::buildForEntity($element, $routeParameter);
    }

    public static function buildForStaticRoute(array $routeParameter, array $context = []): array
    {
        return self::buildRouteParams(RouteItemInterface::STATIC_ROUTE, $routeParameter, $context);
    }

    /**
     * @deprecated use buildForStaticRoute() instead. locale and site will be injected automatically, if given
     */
    public static function buildForStaticRouteWithRequest(array $routeParameter, Request $request): array
    {
        return self::buildForStaticRoute($routeParameter);
    }

    public static function buildForSymfonyRoute(array $routeParameter, array $context = []): array
    {
        return self::buildRouteParams(RouteItemInterface::SYMFONY_ROUTE, $routeParameter, $context);
    }

    /**
     * @deprecated use buildForSymfonyRoute() instead. locale and site will be injected automatically, if given
     */
    public static function buildForSymfonyRouteWithRequest(array $routeParameter, Request $request): array
    {
        return self::buildForSymfonyRoute($routeParameter);
    }

    private static function buildRouteParams(
        ?string $routeType,
        array $routeParameter,
        array $context,
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

        return [Definitions::ATTRIBUTE_I18N_ROUTE_IDENTIFIER => $params];
    }
}
