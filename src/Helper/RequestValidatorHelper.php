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

namespace I18nBundle\Helper;

use I18nBundle\Definitions;
use I18nBundle\Tool\System;
use Pimcore\Http\Request\Resolver\PimcoreContextResolver;
use Pimcore\Http\RequestHelper;
use Symfony\Component\HttpFoundation\Request;

class RequestValidatorHelper
{
    public function __construct(
        protected RequestHelper $requestHelper,
        protected PimcoreContextResolver $pimcoreContextResolver
    ) {
    }

    public function isValidForRedirect(Request $request, bool $allowFrontendRequestByAdmin = true): bool
    {
        if ($this->pimcoreContextResolver->matchesPimcoreContext($request, PimcoreContextResolver::CONTEXT_ADMIN)) {
            return false;
        }

        if (!$this->pimcoreContextResolver->matchesPimcoreContext($request, PimcoreContextResolver::CONTEXT_DEFAULT)) {
            return false;
        }

        if ($request->attributes->get('_route') === 'fos_js_routing_js') {
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
        if (!$request->attributes->has(Definitions::ATTRIBUTE_I18N_CONTEXT)) {
            return false;
        }

        return $request->attributes->get(Definitions::ATTRIBUTE_I18N_CONTEXT) === true;
    }

    public function matchesDefaultPimcoreContext(Request $request): bool
    {
        return $this->pimcoreContextResolver->matchesPimcoreContext($request, PimcoreContextResolver::CONTEXT_DEFAULT);
    }
}
