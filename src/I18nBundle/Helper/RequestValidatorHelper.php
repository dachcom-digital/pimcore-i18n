<?php

namespace I18nBundle\Helper;

use I18nBundle\Tool\System;
use Pimcore\Http\Request\Resolver\PimcoreContextResolver;
use Pimcore\Http\RequestHelper;
use Symfony\Component\HttpFoundation\Request;

class RequestValidatorHelper
{
    /**
     * @var RequestHelper
     */
    protected $requestHelper;

    /**
     * @var PimcoreContextResolver
     */
    protected $pimcoreContextResolver;

    /**
     * @param RequestHelper          $requestHelper
     * @param PimcoreContextResolver $contextResolver
     */
    public function __construct(
        RequestHelper $requestHelper,
        PimcoreContextResolver $contextResolver
    ) {
        $this->requestHelper = $requestHelper;
        $this->pimcoreContextResolver = $contextResolver;
    }

    /**
     * @param Request $request
     * @param bool    $allowFrontendRequestByAdmin
     *
     * @return bool
     */
    public function isValidForRedirect(Request $request, $allowFrontendRequestByAdmin = true)
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
    }

    /**
     * @param Request $request
     *
     * @return bool
     */
    public function matchesDefaultPimcoreContext(Request $request)
    {
        return $this->pimcoreContextResolver->matchesPimcoreContext($request, PimcoreContextResolver::CONTEXT_DEFAULT);
    }

    /**
     * @param Request $request
     *
     * @return bool
     */
    public function isFrontendRequestByAdmin(Request $request)
    {
        return $this->requestHelper->isFrontendRequestByAdmin($request);
    }
}
