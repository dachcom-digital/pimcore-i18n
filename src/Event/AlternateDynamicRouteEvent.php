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

namespace I18nBundle\Event;

use I18nBundle\Model\RouteItem\AlternateRouteItemInterface;
use I18nBundle\Model\RouteItem\RouteItemInterface;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Contracts\EventDispatcher\Event;

class AlternateDynamicRouteEvent extends Event
{
    protected string $type;
    protected array $alternateRouteItems = [];
    protected RouteItemInterface $currentRouteItem;

    public function __construct(
        string $type,
        array $alternateRouteItems,
        RouteItemInterface $routeItem
    ) {
        $this->type = $type;
        $this->alternateRouteItems = $alternateRouteItems;
        $this->currentRouteItem = $routeItem;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getCurrentRouteItem(): RouteItemInterface
    {
        return $this->currentRouteItem;
    }

    public function isCurrentRouteHeadless(): bool
    {
        return $this->currentRouteItem->isHeadless() === true;
    }

    public function getCurrentRouteName(): string
    {
        return $this->currentRouteItem->getRouteName();
    }

    /**
     * @return array<int, AlternateRouteItemInterface>
     */
    public function getAlternateRouteItems(): array
    {
        return $this->alternateRouteItems;
    }

    public function getCurrentLocale(): ?string
    {
        return $this->currentRouteItem->getLocaleFragment();
    }

    public function getCurrentRouteAttributes(): ParameterBag
    {
        return $this->currentRouteItem->getRouteAttributesBag();
    }

    public function getCurrentRouteParameters(): ParameterBag
    {
        return $this->currentRouteItem->getRouteParametersBag();
    }
}
