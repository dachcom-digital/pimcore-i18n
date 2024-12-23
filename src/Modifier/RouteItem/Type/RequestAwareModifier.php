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
use I18nBundle\Resolver\PimcoreAdminSiteResolverInterface;
use Pimcore\Http\Request\Resolver\SiteResolver;
use Pimcore\Http\RequestHelper;
use Pimcore\Model\Document;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class RequestAwareModifier implements RouteItemModifierInterface
{
    public function __construct(
        protected RequestStack $requestStack,
        protected RequestHelper $requestHelper
    ) {
    }

    public function supportParameters(string $type, RouteItemInterface $routeItem, array $parameters, array $context): bool
    {
        return $this->requestStack->getMainRequest() instanceof Request;
    }

    public function supportRequest(string $type, RouteItemInterface $routeItem, Request $request, array $context): bool
    {
        return true;
    }

    public function modifyByParameters(RouteItemInterface $routeItem, array $parameters, array $context): void
    {
        if (!$this->requestStack->getMainRequest() instanceof Request) {
            return;
        }

        $this->modify($routeItem, $this->requestStack->getMainRequest());
    }

    public function modifyByRequest(RouteItemInterface $routeItem, Request $request, array $context): void
    {
        $this->modify($routeItem, $request);
    }

    protected function modify(RouteItemInterface $routeItem, Request $request): void
    {
        $isFrontendRequestByAdmin = $this->requestHelper->isFrontendRequestByAdmin($request);

        if (!$routeItem->getRouteContextBag()->has('site') || $routeItem->getRouteContextBag()->get('site') === null) {
            if ($request->attributes->has(SiteResolver::ATTRIBUTE_SITE)) {
                $routeItem->getRouteContextBag()->set('site', $request->attributes->get(SiteResolver::ATTRIBUTE_SITE));
            } elseif ($request->attributes->has(PimcoreAdminSiteResolverInterface::ATTRIBUTE_ADMIN_EDIT_MODE_SITE)) {
                $routeItem->getRouteContextBag()->set('site', $request->attributes->get(PimcoreAdminSiteResolverInterface::ATTRIBUTE_ADMIN_EDIT_MODE_SITE));
            }
        }

        if (!$routeItem->getRouteParametersBag()->has('_locale')) {
            if ($request->attributes->has('_locale')) {
                $routeItem->getRouteParametersBag()->set('_locale', $request->getLocale());
            } elseif ($request->attributes->get('_route') === 'pimcore_admin_document_page_areabrick-render-index-editmode' && $request->request->has('documentId')) {
                $document = Document::getById($request->request->get('documentId'));
                if ($document instanceof Document) {
                    $routeItem->getRouteParametersBag()->set('_locale', $document->getProperty('language'));
                }
            }
        }

        $routeItem->getRouteContextBag()->set('isFrontendRequestByAdmin', $isFrontendRequestByAdmin);
    }
}
