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

use Pimcore\Model\DataObject\Data\UrlSlug;
use Pimcore\Model\Element\ElementInterface;
use Symfony\Component\HttpFoundation\ParameterBag;

interface BaseRouteItemInterface
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

    public function isFrontendRequestByAdmin(): bool;

    public function hasEntity(): bool;

    public function getEntity(): ?ElementInterface;

    public function setEntity(?ElementInterface $entity): void;

    public function hasRouteName(): bool;

    public function getRouteName(): ?string;

    public function setRouteName(?string $routeName): void;

    public function getUrlSlug(): ?UrlSlug;

    public function setUrlSlug(?UrlSlug $urlSlug): void;
}
