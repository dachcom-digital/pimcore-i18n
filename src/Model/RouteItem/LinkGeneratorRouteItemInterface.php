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

namespace I18nBundle\Model\RouteItem;

use Symfony\Component\HttpFoundation\ParameterBag;

interface LinkGeneratorRouteItemInterface
{
    public function getType(): string;

    public function isHeadless(): bool;

    public function getRouteParametersBag(): ParameterBag;

    public function getRouteParameters(): array;

    public function getRouteAttributesBag(): ParameterBag;

    public function getRouteAttributes(): array;

    public function getRouteContextBag(): ParameterBag;

    public function getRouteContext(): array;

    public function hasLocaleFragment(): bool;

    public function getLocaleFragment(): ?string;

    public function getRouteName(): ?string;
}
