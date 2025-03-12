<?php

declare(strict_types=1);

/**
 * This source file is available under two different licenses:
 *   - GNU General Public License version 3 (GPLv3)
 *   - DACHCOM Commercial License (DCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) DACHCOM.DIGITAL AG (https://www.dachcom-digital.com)
 * @license    GPLv3 and DCL
 */

namespace I18nBundle\Adapter\PathGenerator;

use I18nBundle\Context\I18nContextInterface;
use I18nBundle\I18nEvents;
use I18nBundle\Model\RouteItem\AlternateRouteItemInterface;
use I18nBundle\Model\RouteItem\RouteItemInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class DataObject extends DynamicRoute
{
    public function __construct(
        protected RouterInterface $router,
        protected EventDispatcherInterface $eventDispatcher
    ) {}

    public function getUrls(I18nContextInterface $i18nContext, bool $onlyShowRootLanguages = false): array
    {
        return $this->buildAlternateRoutesStack(
            $i18nContext,
            RouteItemInterface::DATA_OBJECT_ROUTE,
            I18nEvents::PATH_ALTERNATE_DATA_OBJECT_ROUTE
        );
    }

    /**
     * @throws \Exception
     */
    protected function generateLink(AlternateRouteItemInterface $routeItem): string
    {
        $routeParameters = $this->alternateRouteItemTransformer->reverseTransformToArray($routeItem, [
            'type' => RouteItemInterface::DATA_OBJECT_ROUTE,
        ]);

        if ($routeItem->getEntity() === null || $routeItem->getRouteContextBag()->get('urlSlug') === null) {
            throw new \RuntimeException(
                'Cannot create Data Object route URL. Object and/or UrlSlug is missing!'
            );
        }

        return $this->router->generate(
            $routeItem->getRouteName(),
            $routeParameters,
            UrlGeneratorInterface::ABSOLUTE_URL
        );
    }
}
