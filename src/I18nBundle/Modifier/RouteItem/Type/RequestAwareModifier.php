<?php

namespace I18nBundle\Modifier\RouteItem\Type;

use I18nBundle\Model\RouteItem\RouteItemInterface;
use I18nBundle\Resolver\PimcoreAdminSiteResolverInterface;
use Pimcore\Http\Request\Resolver\SiteResolver;
use Pimcore\Http\RequestHelper;
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

        if ($request->attributes->has('_locale') && !$routeItem->getRouteParametersBag()->has('_locale')) {
            $routeItem->getRouteParametersBag()->set('_locale', $request->getLocale());
        }

        $routeItem->getRouteContextBag()->set('isFrontendRequestByAdmin', $isFrontendRequestByAdmin);
    }
}
