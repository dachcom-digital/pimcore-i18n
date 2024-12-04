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

namespace I18nBundle\Transformer;

use I18nBundle\Model\RouteItem\RouteItemInterface;

interface TransformerInterface
{
    public function transform(RouteItemInterface $routeItem, array $context = []): mixed;

    public function reverseTransform(mixed $transformedRouteItem, array $context = []): RouteItemInterface;

    public function reverseTransformToArray(mixed $transformedRouteItem, array $context = []): array;
}
