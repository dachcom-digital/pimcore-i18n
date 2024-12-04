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
use Symfony\Component\HttpFoundation\Request;

interface RouteItemModifierInterface
{
    public function supportParameters(string $type, RouteItemInterface $routeItem, array $parameters, array $context): bool;

    public function supportRequest(string $type, RouteItemInterface $routeItem, Request $request, array $context): bool;

    public function modifyByParameters(RouteItemInterface $routeItem, array $parameters, array $context): void;

    public function modifyByRequest(RouteItemInterface $routeItem, Request $request, array $context): void;
}
