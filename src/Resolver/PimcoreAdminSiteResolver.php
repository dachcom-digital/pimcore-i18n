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

namespace I18nBundle\Resolver;

use Pimcore\Http\Request\Resolver\SiteResolver;
use Pimcore\Http\RequestHelper;
use Pimcore\Model\Site;
use Symfony\Component\HttpFoundation\Request;

class PimcoreAdminSiteResolver implements PimcoreAdminSiteResolverInterface
{
    protected SiteResolver $siteResolver;
    protected RequestHelper $requestHelper;

    public function __construct(
        SiteResolver $siteResolver,
        RequestHelper $requestHelper
    ) {
        $this->siteResolver = $siteResolver;
        $this->requestHelper = $requestHelper;
    }

    public function setAdminSite(Request $request, Site $site): void
    {
        $request->attributes->set(static::ATTRIBUTE_ADMIN_EDIT_MODE_SITE, $site);
    }

    public function hasAdminSite(Request $request): bool
    {
        if ($request->attributes->has(self::ATTRIBUTE_ADMIN_EDIT_MODE_SITE)) {
            return true;
        }

        return false;
    }

    public function getAdminSite(Request $request): ?Site
    {
        if ($request->attributes->has(self::ATTRIBUTE_ADMIN_EDIT_MODE_SITE)) {
            return $request->attributes->get(self::ATTRIBUTE_ADMIN_EDIT_MODE_SITE);
        }

        return null;
    }
}
