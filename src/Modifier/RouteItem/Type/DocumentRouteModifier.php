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

namespace I18nBundle\Modifier\RouteItem\Type;

use I18nBundle\Model\RouteItem\RouteItemInterface;
use Pimcore\Model\Document;
use Pimcore\Tool\Frontend;
use Symfony\Component\HttpFoundation\Request;

class DocumentRouteModifier implements RouteItemModifierInterface
{
    public function supportParameters(string $type, RouteItemInterface $routeItem, array $parameters, array $context): bool
    {
        if ($type !== RouteItemInterface::DOCUMENT_ROUTE) {
            return false;
        }

        if (!$routeItem->getEntity() instanceof Document) {
            return false;
        }

        return true;
    }

    public function supportRequest(string $type, RouteItemInterface $routeItem, Request $request, array $context): bool
    {
        if ($type !== RouteItemInterface::DOCUMENT_ROUTE) {
            return false;
        }

        if (!array_key_exists('document', $context)) {
            return false;
        }

        return true;
    }

    public function modifyByParameters(RouteItemInterface $routeItem, array $parameters, array $context): void
    {
        /** @var Document $document */
        $document = $routeItem->getEntity();

        $routeItem->getRouteParametersBag()->set('_locale', $document->getProperty('language'));

        if (!$routeItem->getRouteContextBag()->has('site') && null !== $site = Frontend::getSiteForDocument($document)) {
            $routeItem->getRouteContextBag()->set('site', $site);
        }
    }

    public function modifyByRequest(RouteItemInterface $routeItem, Request $request, array $context): void
    {
        /** @var Document $document */
        $document = $context['document'];

        $routeItem->setEntity($document);
        $routeItem->getRouteParametersBag()->set('_locale', $document->getProperty('language'));
    }
}
