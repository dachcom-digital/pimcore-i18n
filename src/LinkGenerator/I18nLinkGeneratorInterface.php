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

namespace I18nBundle\LinkGenerator;

use I18nBundle\Model\RouteItem\LinkGeneratorRouteItemInterface;
use Pimcore\Model\DataObject\Concrete;

interface I18nLinkGeneratorInterface
{
    public function getStaticRouteName(Concrete $object): string;

    public function generateRouteItem(Concrete $object, LinkGeneratorRouteItemInterface $linkGeneratorRouteItem): LinkGeneratorRouteItemInterface;
}
