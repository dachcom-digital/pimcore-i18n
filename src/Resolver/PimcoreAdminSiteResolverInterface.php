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

use Pimcore\Model\Site;
use Symfony\Component\HttpFoundation\Request;

interface PimcoreAdminSiteResolverInterface
{
    public const ATTRIBUTE_ADMIN_EDIT_MODE_SITE = '_editmode_admin_site';

    public function getAdminSite(Request $request): ?Site;

    public function hasAdminSite(Request $request): bool;

    public function setAdminSite(Request $request, Site $site): void;
}
