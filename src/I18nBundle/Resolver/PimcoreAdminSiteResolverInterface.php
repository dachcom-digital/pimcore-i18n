<?php

namespace I18nBundle\Resolver;

use Pimcore\Model\Site;
use Symfony\Component\HttpFoundation\Request;

interface PimcoreAdminSiteResolverInterface
{
    public const ATTRIBUTE_ADMIN_EDIT_MODE_SITE = '_editmode_admin_site';

    public function getAdminSite(Request $request): ?Site;

    public function hasAdminSite(Request $request): bool;

    public function setAdminSite(Request $request, Site $site): void;
}
