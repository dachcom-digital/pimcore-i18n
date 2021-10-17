<?php

namespace I18nBundle\Helper;

use I18nBundle\Definitions;
use I18nBundle\Tool\System;
use Pimcore\Http\Request\Resolver\PimcoreContextResolver;
use Pimcore\Http\RequestHelper;
use Symfony\Component\HttpFoundation\Request;

class RequestValidatorHelper
{
    protected RequestHelper $requestHelper;
    protected PimcoreContextResolver $pimcoreContextResolver;

    public function __construct(
        RequestHelper $requestHelper,
        PimcoreContextResolver $contextResolver
    ) {
        $this->requestHelper = $requestHelper;
        $this->pimcoreContextResolver = $contextResolver;
    }

    public function isValidForRedirect(Request $request, bool $allowFrontendRequestByAdmin = true): bool
    {
        if ($this->pimcoreContextResolver->matchesPimcoreContext($request, PimcoreContextResolver::CONTEXT_ADMIN)) {
            return false;
        }

        if (!$this->pimcoreContextResolver->matchesPimcoreContext($request, PimcoreContextResolver::CONTEXT_DEFAULT)) {
            return false;
        }

        if ($allowFrontendRequestByAdmin === false && $this->requestHelper->isFrontendRequestByAdmin($request)) {
            return false;
        }

        if (System::isInCliMode()) {
            return false;
        }

        return true;
    }

    public function matchesI18nContext(Request $request): bool
    {
        if(!$request->attributes->has(Definitions::ATTRIBUTE_I18N_CONTEXT)) {
            return false;
        }

        return $request->attributes->get(Definitions::ATTRIBUTE_I18N_CONTEXT) === true;
    }

    public function matchesDefaultPimcoreContext(Request $request): bool
    {
        return $this->pimcoreContextResolver->matchesPimcoreContext($request, PimcoreContextResolver::CONTEXT_DEFAULT);
    }

    public function isFrontendRequestByAdmin(Request $request): bool
    {
        return $this->requestHelper->isFrontendRequestByAdmin($request);
    }
}
